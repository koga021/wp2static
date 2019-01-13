<?php

class StaticHtmlOutput_GitLab extends StaticHtmlOutput_SitePublisher {

    public function __construct() {
        $target_settings = array(
            'general',
            'wpenv',
            'gitlab',
            'advanced',
        );

        if ( isset( $_POST['selected_deployment_option'] ) ) {
            require_once dirname( __FILE__ ) .
                '/../library/StaticHtmlOutput/PostSettings.php';

            $this->settings = WPSHO_PostSettings::get( $target_settings );
        } else {
            require_once dirname( __FILE__ ) .
                '/../library/StaticHtmlOutput/DBSettings.php';

            $this->settings = WPSHO_DBSettings::get( $target_settings );
        }

        $this->export_file_list =
            $this->settings['wp_uploads_path'] .
                '/WP-STATIC-EXPORT-GITLAB-FILES-TO-EXPORT.txt';
        $archiveDir = file_get_contents(
            $this->settings['wp_uploads_path'] .
                '/WP-STATIC-CURRENT-ARCHIVE.txt'
        );

        $this->r_path = '';

        if ( isset( $this->settings['glPath'] ) ) {
            $this->r_path = $this->settings['glPath'];
        }

        // TODO: move this where needed
        require_once dirname( __FILE__ ) .
            '/../library/StaticHtmlOutput/Archive.php';
        $this->archive = new Archive();
        $this->archive->setToCurrentArchive();
        $this->files_in_tree = array();

        $this->api_base = '';

        switch ( $_POST['ajax_action'] ) {
            case 'gitlab_prepare_export':
                $this->prepare_export( true );
                break;
            case 'gitlab_upload_files':
                $this->upload_files();
                break;
            case 'test_gitlab':
                $this->test_file_create();
                break;
        }
    }

    public function createGitLabPagesConfig() {
        // GL doesn't seem to build the pages unless this file is detected
        $config_file = <<<EOD
pages:
  stage: deploy
  script:
  - mkdir .public
  - cp -r * .public
  - mv .public public
  artifacts:
    paths:
    - public
  only:
  - master

EOD;

        $target_path = $this->archive->path . '.gitlab-ci.yml';

        file_put_contents( $target_path, $config_file );

        chmod( $target_path, 0664 );

        // force include the gitlab config file
        $export_line = '.gitlab-ci.yml,.gitlab-ci.yml';

        file_put_contents(
            $this->export_file_list,
            $export_line . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );

        chmod( $this->export_file_list, 0664 );
    }

    public function mergePartialTrees( $items ) {
        $this->files_in_tree = array_merge( $this->files_in_tree, $items );
    }

    public function getFilePathsFromTree( $json_response ) {
        $partial_tree_array = json_decode( (string) $json_response, true );

        $formatted_elements = array();

        foreach ( $partial_tree_array as $object ) {
            if ( $object['type'] === 'blob' ) {
                $formatted_elements[] = $object['path'];
            }
        }

        return $formatted_elements;
    }

    public function getRepositoryTree( $page ) {
        $tree_endpoint = 'https://gitlab.com/api/v4/projects/' .
            $this->settings['glProject'] .
            '/repository/tree?recursive=true&per_page=100&page=' . $page;

        $ch = curl_init();

        curl_setopt( $ch, CURLOPT_URL, $tree_endpoint );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
        curl_setopt( $ch, CURLOPT_HEADER, 1 );
        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );
        curl_setopt( $ch, CURLOPT_USERAGENT, 'WP2Static.com' );
        curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'GET' );

        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            array(
                'PRIVATE-TOKEN: ' .  $this->settings['glToken'],
                'Content-Type: application/json',
            )
        );

        $output = curl_exec( $ch );
        $status_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
        $header_size = curl_getinfo( $ch, CURLINFO_HEADER_SIZE );

        $body = substr( $output, $header_size);
        $header = substr( $output, 0, $header_size);

        $raw_headers = explode(
            "\n",
            trim( mb_substr( $output, 0, $header_size ) )
        );

        unset($raw_headers[0]);

        $headers = array();

        foreach( $raw_headers as $line ) {
          list( $key, $val ) = explode( ':', $line, 2 );
            $headers[strtolower($key)] = trim( $val );
        }

        curl_close( $ch );

        $good_response_codes = array( '200', '201', '301', '302', '304' );

        if ( ! in_array( $status_code, $good_response_codes ) ) {
            require_once dirname( __FILE__ ) .
                '/../library/StaticHtmlOutput/WsLog.php';
            WsLog::l(
                'BAD RESPONSE STATUS (' . $status_code . '): '
            );

            throw new Exception( 'GitLab API bad response status' );
        }

        $total_pages = $headers['x-total-pages'];
        $next_page = $headers['x-next-page'];
        $current_page = $headers['x-page'];

        // if we have results, append them to files to delete array
        $json_items = $body;

        $this->mergePartialTrees(
            $this->getFilePathsFromTree( $json_items )
        );

        // if current page is less than total pages
        if ( $current_page < $total_pages ) {
            // call this again with an increment
            $this->getRepositoryTree( $next_page );
        }
    }

    public function getListOfFilesInRepo() {
        $this->getRepositoryTree( 1 );
    }

    public function upload_files() {
        // TODO: move repo file list to flat txt file, once-only generation
        $this->getListOfFilesInRepo();

        $filesRemaining = $this->get_remaining_items_count();

        if ( $filesRemaining < 0 ) {
            echo 'ERROR';
            die();
        }

        $batch_size = $this->settings['glBlobIncrement'];

        if ( $batch_size > $filesRemaining ) {
            $batch_size = $filesRemaining;
        }

        $lines = $this->get_items_to_export( $batch_size );

        $files_data = array();

        foreach ( $lines as $line ) {
            list($fileToTransfer, $targetPath) = explode( ',', rtrim( $line ) );

            $fileToTransfer = $this->archive->path . $fileToTransfer;

            if ( ! is_file( $fileToTransfer ) ) {
                continue;
            }

            if ( in_array( $targetPath, $this->files_in_tree ) ) {

                // TODO: quick filesize comparision via
                // https://docs.gitlab.com/ee/api/repository_files.html
                $files_data[] = array(
                    'action' => 'update',
                    'file_path' => $targetPath,
                    'content' => base64_encode(
                        file_get_contents( $fileToTransfer )
                    ),
                    'encoding' => 'base64',
                );
            } else {
                $files_data[] = array(
                    'action' => 'create',
                    'file_path' => $targetPath,
                    'content' => base64_encode(
                        file_get_contents( $fileToTransfer )
                    ),
                    'encoding' => 'base64',
                );
            }
        }

        if ( isset( $this->settings['glBlobDelay'] ) &&
            $this->settings['glBlobDelay'] > 0 ) {
            sleep( $this->settings['glBlobDelay'] );
        }

        $commits_endpoint = 'https://gitlab.com/api/v4/projects/' .
            $this->settings['glProject'] . '/repository/commits';

        try {
            $ch = curl_init();

            curl_setopt( $ch, CURLOPT_URL, $commits_endpoint );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
            curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
            curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
            curl_setopt( $ch, CURLOPT_HEADER, 0 );
            curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );
            curl_setopt( $ch, CURLOPT_POST, 1 );
            curl_setopt( $ch, CURLOPT_USERAGENT, 'WP2Static.com' );
            curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'POST' );

            $post_options = array(
                'branch' => 'master',
                'commit_message' => 'WP2Static Deployment',
                'actions' => $files_data,
            );

            curl_setopt(
                $ch,
                CURLOPT_POSTFIELDS,
                json_encode( $post_options )
            );

            curl_setopt(
                $ch,
                CURLOPT_HTTPHEADER,
                array(
                    'PRIVATE-TOKEN: ' .  $this->settings['glToken'],
                    'Content-Type: application/json',
                )
            );

            $output = curl_exec( $ch );
            $status_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );

            curl_close( $ch );

            $good_response_codes = array( '200', '201', '301', '302', '304' );

            if ( ! in_array( $status_code, $good_response_codes ) ) {
                require_once dirname( __FILE__ ) .
                    '/../library/StaticHtmlOutput/WsLog.php';
                WsLog::l(
                    'BAD RESPONSE STATUS (' . $status_code . '): '
                );

                throw new Exception( 'GitLab API bad response status' );
            }
        } catch ( Exception $e ) {
            require_once dirname( __FILE__ ) .
                '/../library/StaticHtmlOutput/WsLog.php';
            WsLog::l( 'GITLAB EXPORT: error encountered' );
            WsLog::l( $e );
            throw new Exception( $e );
            return;
        }

        $filesRemaining = $this->get_remaining_items_count();

        if ( $filesRemaining > 0 ) {

            if ( defined( 'WP_CLI' ) ) {
                $this->upload_files();
            } else {
                echo $filesRemaining;
            }
        } else {
            $this->createGitLabPagesConfig();

            if ( ! defined( 'WP_CLI' ) ) {
                echo 'SUCCESS';
            }
        }
    }

    public function test_file_create() {
        $remote_path = 'https://gitlab.com/api/v4/projects/' .
            $this->settings['glProject'] . '/repository/commits';

        try {
            $ch = curl_init();

            curl_setopt( $ch, CURLOPT_URL, $remote_path );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
            curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
            curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
            curl_setopt( $ch, CURLOPT_HEADER, 0 );
            curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );
            curl_setopt( $ch, CURLOPT_POST, 1 );
            curl_setopt( $ch, CURLOPT_USERAGENT, 'WP2Static.com' );
            curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'POST' );

            $post_options = array(
                'branch' => 'master',
                'commit_message' => 'test deploy from plugin',
                'actions' => array(
                    array(
                        'action' => 'create',
                        'file_path' => '.wpsho_' . time(),
                        'content' => 'test file',
                    ),
                    array(
                        'action' => 'create',
                        'file_path' => '.wpsho2_' . time(),
                        'content' => 'test file 2',
                    ),
                ),
            );

            curl_setopt(
                $ch,
                CURLOPT_POSTFIELDS,
                json_encode( $post_options )
            );

            curl_setopt(
                $ch,
                CURLOPT_HTTPHEADER,
                array(
                    'PRIVATE-TOKEN: ' .  $this->settings['glToken'],
                    'Content-Type: application/json',
                )
            );

            $output = curl_exec( $ch );
            $status_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );

            curl_close( $ch );

            $good_response_codes = array( '200', '201', '301', '302', '304' );

            if ( ! in_array( $status_code, $good_response_codes ) ) {
                require_once dirname( __FILE__ ) .
                    '/../library/StaticHtmlOutput/WsLog.php';
                WsLog::l(
                    'BAD RESPONSE STATUS (' . $status_code . '): '
                );

                throw new Exception( 'GitLab API bad response status' );
            }
        } catch ( Exception $e ) {
            require_once dirname( __FILE__ ) .
                '/../library/StaticHtmlOutput/WsLog.php';
            WsLog::l( 'GITLAB EXPORT: error encountered' );
            WsLog::l( $e );
            throw new Exception( $e );
            return;
        }

        if ( ! defined( 'WP_CLI' ) ) {
            echo 'SUCCESS';
        }
    }
}

$gitlab = new StaticHtmlOutput_GitLab();
