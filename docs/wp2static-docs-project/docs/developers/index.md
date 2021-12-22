# Developing a static site generator for WordPress
## Webserver setup

WP2Static should work on any web server setup, see System Requirements for the least you should consider. I’m developing deployment and utility scripts for an optimized WordPress server for WP2Static testing on a $5/month Vultr VPS with OpenBSD -current as the operating system. I’ll soon start sharing here the code I use for anyone who wants to replicate or borrow parts from. It’s not a very mainstream setup, but I find OpenBSD to be the best fit for me. Webserver setup full article
## Extending WP2Static

WP2Static aims to be as extensible as possible, while maintaining a minimal, solid core. WP-CLI commands allow for easily piping WP2Static commands into your own scripted workflows and programmatic access to execute and modify WP2Static behaviour. Filters and actions In lieu of comprehensive documentation about each action/hook/filter available in WP2Static and its Add-ons, I’ll just dump the current available actions and filters from within the core WP2Static plugin’s codebase. Below list is derived from running git grep -e do_action --or -e apply_filter. Extending WP2Static full article
## WP-CLI

WP-CLI is a developer-friendly way to manage WordPress sites. WP2Static integrates with WP-CLI, adding useful commands to generate and deploy your static site. WP-CLI commands Running the command wp wp2static will give you all the avaiable options and is the best way to know which commands are available on your system. WP2Static Add-ons may provide additional commands. WP-CLI commands will apply to the site relative to the system path you run them from. WP-CLI full article