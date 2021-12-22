# WordPress static website add-ons

## Algolia Search Add-On
Enable Algolia search for your static site.
## How it works

The Algolia search add-on is complimentary to WebDevStudio’s [WP Search with Algolia](https://wordpress.org/plugins/wp-search-with-algolia) plugin, which is a maintained fork of Algolia’s abandoned official WP plugin.

Whilst that plugin does a great job of providing Algolia search functions to WordPress, this add-on completes a few extra steps required to make it compatible with a static site, such as what WP2Static generates:

* rewrite URLs sent to Algolia to be site root relative (ie, https://example.com/sample-page/ becomes /sample-page/

We do this because your WordPress development site is almost always going to be a different domain/subdomain to where you host your WP2Static generated static site. By rewriting to site root relative URLs, we allow the InstantSearch to work identically on your development server as in your static site, for less unexpected suprises when you publish!

* loads Algolia’s InstantSearch on dedicated /search/ homepage

In development, on a typical WordPress site, Algolia InstantSearch doesn’t require any server-side code to run, but it does use WordPress' templating system to detect when we’re viewing a “search page” and only then load the InstantSearch required template. We create this /search/ page and force WP to consider it a “search” page, so that the Algolia plugin renders it’s search form/results there.

* route WP default search queries to new search page

By default, WP will send queries to https://example.com/?s=my+query, but in a static site, we can’t use the homepage for both regular homepage and search results page, so we route search queries to the dedicated page we created, ie /search/?s=my+query.
## Configuring the WP Search with Algolia plugin

We currently support the Instantsearch.js mode of the [WP Search with Algolia](https://wordpress.org/plugins/wp-search-with-algolia) plugin. Choose this from the Algolia > Search Page options and “Save changes”.
## WP-CLI commands

As is often the case, I needed some scripts to quickly check what was going on in Algolia while developing this add-on. Creating WP-CLI commands for these scripts helped me in development, but may also help you during regular usage or if you’re looking to extend this add-on or create your own.

* ```wp wp2static algolia list_indices```
* ```wp wp2static algolia list_objects```

## Troubleshooting

* check WP2Static’s Logs for any errors
* check webserver/PHP’s error logs on server
* check indices in Algolia’s web UI
* use the WP-CLI commands above to check your Algolia indices and objects


## BunnyCDN Deployment Add-On
Auto-deploy your generated static site to BunnyCDN.
Configuration
via UI

Input your BunnyCDN connection settings via the WP2Static > BunnyCDN menu.
via WP-CLI

wp wp2static bunnycdn options set name value
Available option 	Example input
bunnycdnAccountAPIKey 	9cff0fd9-7b86-4dad-be05-dabf5959e
bunnycdnStorageZoneName 	mystoragezonename
How it works

The BunnyCDN deployment add-on deploys your static site files to a BunnyCDN StorageZone and purges cache from the connected Pull Zone. The BunnyCDN Storage API is used to transfer files, currently limited via the API to 1 file at a time. WP2Static’s internal deploy cache is used to only transfer files changed between deploys.

A BunnyCDN Storage Zone allows files to be stored on BunnyCDN’s servers vs pulling from an origin server. A BunnyCDN Pull Zone sits in front of this Storage Zone, allowing static site hosting to work by routing requests to your custom domain to be served by files in the Storage Zone.

BunnyCDN takes care of distributing your static site across all it’s geographic regions for fastest load speeds to all your site’s visitors.

You can further optimize your site’s delivery using the Pull Zone settings available via BunnyCDN’s web UI, such as cache expiration times, number of datacentres, disabling cookies, image optimization, CSS/JS minification and more. Some of these options incur additional costs.

As we need the Account API Key to purge cache, we reduce the amount of options required for this add-on and use the API to find the Storage Zone Access Key when making requests to the Storage Zone API. Similarily, we detect the Storage Zone’s connected Pull Zone ID from the API. This just adds an extra API call behind the scenes vs requiring the user to input additional fields, like in the V6 BunnyCDN deployment option.
## Configuration within WP2Static

There are only a few options you need to set in the plugin.

In WP2Static’s core “Options” menu (or via WP-CLI), set the Destination URL value to the final URL you plan to host your site on (ie, https://example.com.

In WP2Static’s BunnyCDN options, you’ll set the following:

* Account API Key
* Storage Zone Name

Configuration within BunnyCDN

Requirements for using this add-on with BunnyCDN:

* BunnyCDN Storage Zone
* BunnyCDN Pull Zone linked to Storage Zone
* BunnyCDN Account API Key
* Custom hostname added to Pull Zone
* CNAME DNS record from your domain to Pull Zone hostname

Step by step setup for a new BunnyCDN site

    In your BunnyCDN dashboard, navigate to Account and take note of your API key somewhere secure.

Get BunnyCDN Account API Key

    Save this in WP2Static > BunnyCDN > Account API Key

    In your BunnyCDN dashboard, navigate to Storage Zones and create one.

Create Storage Zone

    Save the Storage Zone’s name in WP2Static > BunnyCDN > Storage Zone Name

    Connect a new Pull Zone to your Storage Zone within BunnyCDN. From within your Storage Zone settings, choose “Connect Pull Zone”

Connect Pull Zone

    Enter any unique name for your pull zone and choose “Add Pull Zone” at bottom of screen.

Name Pull Zone

    Point your custom domain to BunnyCDN via the Pull Zone. Add your domain as a new hostname in your Pull Zone, adding the CNAME record in your DNS settings using the value provided by BunnyCDN. Ensure it’s all connected and then it’s recommended to use the Force SSL setting.

Connect Custom Domain

    Set your BunnyCDN site’s URL as your Destination URL within WP2Static options:

Add your Destination URL in WP2Static

    You’re all setup! You can now deploy your site as usual via WP2Static (optionally clearing caches if you’ve previously done any deploys)

    After deploying, the custom hostname you used with the BunnyCDN Pull Zone should now show a static version of yoor WordPress site:

Successful Deploy

    A successful deployment should create Logs like this:

Successful Logs
Managing your Storage Zone

To help me develop this add-on and troubleshoot when things go wrong, I’ve added the following WP-CLI commands to the BunnyCDN add-on:

    wp wp2static bunnycdn storage_zone_files list print out all filenames in the Storage Zone (this currently only prints items in the root directory, but the delete command below will delete all files recursively
    wp wp2static bunnycdn storage_zone_files count get total number of filenames in the Storage Zone
    wp wp2static bunnycdn storage_zone_files delete delete all filenames in the Storage Zone (omitting --force will prompt for confirmation)

Troubleshooting

    check WP2Static’s Logs for any errors
    check Storage Zone / Pull Zone in BunnyCDN web UI
    use the WP-CLI commands above to check your Storage Zone files
    test your BunnyCDN API token:
        via CLI:

lists contents of Storage Zone
curl --include \
     --header "Accept: application/json" \
     --header "AccessKey: STORAGE_ZONE_API_KEY" \
  'https://storage.bunnycdn.com/STORAGE_ZONE_NAME/'

    check your BunnyCDN API Key permissions (don’t use the read-only API Key)

Notes

BunnyCDN is a great CDN option, having a small, responsive and friendly team in Slovenia and offering edge locations where other providers don’t. Their web UI is quite easy to use. The Storage Zone API is unfortunately a bit lacking in ability to bulk upload files.

## Cloudflare Workers static site hosting

Auto-deploy your WordPress static site to Cloudflare Workers.
Configuration
via UI

Input your Cloudflare Workers connection settings via the WP2Static > Cloudflare Workers menu.
via WP-CLI

```
wp wp2static cloudflare_workers options set name value
Available option 	Example input
accountID 	4c87e31c658e4feca573fe02fc225e8
namespaceID 	683ba08756da4a9dadf0d6fe9022633
apiToken 	a3076fae62e5485aa083d51ba8ca037
useBulkUpload 	1
```
How it works

The Cloudflare Workers deployment add-on deploys your static site files to a Cloudflare Workers KV namespace. It adds a key for each path (path), along with an extra key (path_ct) to store the content type. An example of 1 file uploaded, would result in 2 keys:



| Key 	|               Value                     |
|-------|:---------------------------------------:|
| /	    | My WordPress Site...                    |
| /_ct 	| 	text/html                             |

A Workers script (you need to setup) is used to read the files and correct content type and serve these to the user, caching at their nearest edge locations.

You’ll need to add a Route for the Worker for it to run on the intended domain/subdomain.

The resulting deployment will function similar to if you deploy with Cloudflare’s Wrangler CLI tool.

An example script for Workers to use the KV store:
```
addEventListener('fetch', event => {
  event.respondWith(handleRequest(event.request));
})

async function handleRequest(request) {
  const url = new URL(request.url);
  const uri = url.pathname;

  const [value, content_type] = await Promise.all(
    [
      FIRST_KV_NAMESPACE.get(uri),
      FIRST_KV_NAMESPACE.get(uri + "_ct")
    ]
  );

  if (value === null || content_type === null) {
    return new Response("Page not found", {status: 404});
  }

  const init = {
    headers: {
      'Content-Type': content_type + ';charset=UTF-8',
      'Cache-Control': "public",
      // set Expires to 12hrs from now
      'Expires': new Date(Date.now() + 43200 * 1000).toUTCString(),
    },
  };

  return new Response(value, init);
}
```
Configuration within WP2Static

There are only a few options you need to set in the plugin.

In WP2Static’s core “Options” menu (or via WP-CLI), set the Destination URL value to the final URL you plan to host your site on (ie, https://example.com, not the *.workers.dev subdomain (unless you want to use that during testing deployments).

In WP2Static’s Cloudflare Workers menu, you’ll set the Account ID to your Cloudflare account ID you can find within your URLs, ie if visiting a Workers admin URL, it will be the https://dash.cloudflare.com/ACCOUNT_ID/workers ACCOUNT_ID hash.

Set your Namespace ID to your target namespace’s ID as shown in the Worker KV page.

Set your API Token to a token you create via My Profile > API Tokens.

Bulk uploads option is on by default. This greatly speeds up the transfers to Cloudflare, but if you need to upload each file one by one to overcome some network limitations, you can disable this.
Configuration within Cloudflare

Requirements for using this add-on with Cloudflare:

    $5/month KV Unlimited plan
    a domain’s DNS managed by Cloudflare
    a Worker with a function like above and a Route set to a DNS record for your domain (ie, a subdomain or naked domain)
    a Workers KV namespace
    a KV Namespace Binding in your worker to the KV namespace (ie, in this doc, we’ve used FIRST_KV_NAMESPACE)

Step by step setup for a new Workers site

    In your Cloudflare dashboard, navigate to Workers, choose “Create a Worker”

Create Worker

    paste the example script on this page into the editor screen shown and choose “Save and Deploy”:

Save and Deploy

    return to the Workers menu and “Rename and Deploy” your newly created Worker to something more mmorable:

Rename Worker

This gives you your internal *.workers.dev subdomain.

    setup a new KV Namespace

Navigate to KV page “Add” a new Namespace using a name of your choosing:

Add KV Namespace

    copy your KV namespace’s ID and paste into the Cloudflare Add-on’s options:

Copy KV Namespace ID

Save Namespace ID

    copy your Cloudflare Account ID and paste into the Cloudflare Add-on’s options:

Copy Account ID

Save Account ID

    Navigate to your Profile > API Tokens > “Create Token” > “Create Custom Token” > “Get started”

Create Custom API Token

    Set your API Token’s names and the Account:Workers KV Storage:Edit permissions

You can leave the default Account Resources set to Include:All accounts or narrow the permissions to your needs

Set Custom API Token Permissions

    Proceed to the confirmation screen and choose “Create Token”

    copy your Cloudflare API Token and paste into the Cloudflare Add-on’s options:

Copy API Token

Save API Token

    Add KV Namespace Binding to your Worker. Workers > YOUR_NEW_WORKER > “Add variable”

    Set the Variable name to FIRST_KV_NAMESPACE and choose your new Worker from the KV Namespace menu:

Set Worker KV Namespace Binding

    Add DNS entry to point to Workers. For a naked domain, add an A record for @ to 192.0.2.1 with Proxy status “Proxied”

This will resolve the record to Cloudflare, allowing the Worker to server requests.

For a subdomain, use the same approach, but use the subdomain, ie www instead of the @.

Create DNS entry

    Add a Route example.com/*, choosing your newly created Worker from the list:

Create DNS entry

If you want to have www redirect to your naked domain, you can also add a CNAME record from www to example.com, but you may need to add an additional route or code to the Worker script to handle the redirect.

    Set your Cloudflare site’s URL as your Destination URL within WP2Static options:

Add your Destination URL in WP2Static

    You’re all setup! You can now deploy your site as usual via WP2Static (optionally clearing caches if you’ve previously done any deploys)

    A successful deployment should create Logs like this:

Successful Logs
Managing your KV namespaces

Cloudflare’s web interface doesn’t currently allow for easily managing KV data.

To help me develop this add-on and troubleshoot when things go wrong, I’ve added the following WP-CLI commands to the Cloudflare Workers add-on:

    wp wp2static cloudflare_workers keys list print out all keys in the namespace
    wp wp2static cloudflare_workers keys count get total number of keys in the namespace
    wp wp2static cloudflare_workers keys delete delete all keys in the namespace (omitting --force will prompt for confirmation)

If you’ve been testing deploys to a KV namespace and filled it with a lot of bad or otherwise unneeded keys, it’s faster to create a new namespace for use in WP2Static and deleting the old namespace.
Troubleshooting

    check WP2Static’s Logs for any errors
    check webserver/PHP’s error logs on server
    check keys in KV namespace in Cloudflare
    use the WP-CLI commands above to check your namespace’s keys and values
    test your Cloudflare API token:
        via CLI:

```curl -X GET "https://api.cloudflare.com/client/v4/user/tokens/verify" \
     -H "Authorization: Bearer YOURAPITOKEN" \
     -H "Content-Type:application/json"```

    check your Cloudflare API token’s permissions (write to KV for correct account/zone)

Notes

You can use the built-in Brotli compression Cloudflare offers without needing to set additional headers in your Workers script. If you’d like to see options to send gzipped data from WP2Static and set gzip headers in your Workers script, let us know.

## Google Cloud Storage Deployment Add-On

Auto-deploy your generated static site to Google Cloud Storage.
Configuration
via UI

Input your Google Cloud Storage connection settings via the WP2Static > Google Cloud Storage menu.
via WP-CLI

wp wp2static google-cloud-storage options set name value
Available option 	Example input
TBC 	
Troubleshooting

* check WP2Static’s Logs for any errors
* check webserver/PHP’s error logs on server
* check Google Cloud Storage’s logs

## Netlify Deployment Add-On

Auto-deploy your generated static site to Netlify.
Configuration
via UI

Input your Netlify connection settings via the WP2Static > Netlify menu.
via WP-CLI

wp wp2static netlify options set name value
Available option 	Example input
siteID 	ID or domain
accessToken 	your personal access token (see below)
Troubleshooting

    check WP2Static’s Logs for any errors
    check webserver/PHP’s error logs on server
    test your Netlify access token:
        via CLI (should return info about your site):

curl -H "Authorization: Bearer PERSONAL_ACCESS_TOKEN" https://api.netlify.com/api/v1/sites/SITE_ID

    use the ZIP deployment add-on and do a manual deploy of your site via their web UI (drag and drop ZIP)
    use the Netlify CLI tool to deploy your generated static site files
    check the Deploys screen for your site in the Netlify UI or CLI tool. If it appears one or more deploys are stuck, you can safely cancel them and redploy. Even if no files need to be uploaded, this can sometimes help force a new deploy to go live.
    download and check the deployed artifacts from Netlify UI
    clear all of WP2Static’s caches and try to redeploy

How it works

This add-on deploys your static site to Netlify via their API. It uses their “digest method”, which first downloads information about all the files already on Netlify’s servers for your site, then compares against the files WP2Static has generated. It then only transfers the files which have changed, saving time on large sites.
What is my Netlify Site ID?

From the Netlify docs:

    Whenever the API requires a :site_id, you can either use the id of a site obtained through the API, or the domain of the site (for example, \mysite.netlify.com or \www.example.com). These two are interchangeable whenever they’re used in API paths.

Where do I get my Netlify Personal Access Token?

You can generate a new personal access token from your Netlify profile’s Applications page.
Rolling back a failed deploy

Netlify’s UI (and probably CLI client) offer easy ways to rollback to a previously good deploy. In the web UI, just choose the previous deployment and hit “Publish deploy”
Downloading deployment artifacts from Netlify

For any deployment done to Netlify, you can download an archive of the deployed files. Go to the specific deployments page and next to the date, there is a little download icon.
Handling redirects and custom headers

Netlify supports setting custom headers or redirects.
Form processing on Netlify

You can easily adjust any contact or other forms in your WordPress site to work with Netlify by adjusting the form’s HTML markup. See Netlify’s forms docs. WP2Static should have a specialised Forms add-on to automatically handle this in the future or you can write your own custom code/plugin to integrate with WP2Static via our hooks/filters.
Serverless functions

Netlify also has their own Functions which can allow adding dynamic functionality or advanced processing to your static site hosted with them.
A warning on vendor lock-in

Whilst Netlify’s Forms and Functions are very convenient and relatively simple to use compared to some alternatives, please be mindful of putting all your eggs in one basket. The beauty of static sites is that they will “run anywhere”. You can use Netlify for your static site hosting and still choose other vendors or your own custom serverless functions for forms, comments, etc.
A note on performance

Netlify uses multiple CDNs behind the scenes to deliver your static site. These are generally fast globally, but they also offer a premium tier with faster speeds. In preliminary testing, I’ve found AWS S3 + CloudFront to be globally faster and there’s a high chance that Cloudflare edge-caching may also be faster. I recommend you do your own testing, considering your target global audience.
A note on usability

Netlify continues to be one of the easiest deployment options, giving a great balance of price (usually free!), ease of management and generally good performance. I recommend Netlify as the easiest deployment option to get startd with, even if just for testing/staging if not production. Their simple drag and drop UI to deploy a ZIP’d static site is brilliant and ability to do quick deployment rollbacks removes a lot of anxiety! Compared to something like AWS, you get real, knowledgable support people to interact with.
Mixed-content warning notifications from Netlify

A useful feature of Netlify, is that they will notify you (email) of any mixed-content detected during their build & deploy process. This is great, but often gives false positives when we’re deploying to a new site.

ie, if we have set out Destination URL as https://example.com but we first deploy to Netlify without our custom domain set to our Netlify site (ie, https://somenewsite.netlify.app), we’ll get those warnings.

If you’re still receiving those warnings and think you shouldn’t - check your site for actual mixed content (ie, http links on an https site). An easy way to find these is in your browser’s console > Network tab. Open that up, then refresh the page to see any URLs with warnings or check the Console tab, which may also show any issues.

## ZIP Deployment Add-On

Auto-deploy your generated static site to a ZIP archive.
Configuration

There is no configuration required for the ZIP add-on. Simply install, activate and when you deploy your site a ZIP will be created (you can download it or find its path via the WP2Static > ZIP menu.