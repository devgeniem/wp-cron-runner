![geniem-github-banner](https://cloud.githubusercontent.com/assets/5691777/14319886/9ae46166-fc1b-11e5-9630-d60aa3dc4f9e.png)

# WP Cron Runner
[![Latest Stable Version](https://poser.pugx.org/devgeniem/wp-cron-runner/v/stable)](https://packagist.org/packages/devgeniem/wp-cron-runner)
[![Total Downloads](https://poser.pugx.org/devgeniem/wp-cron-runner/downloads)](https://packagist.org/packages/devgeniem/wp-cron-runner)
[![Latest Unstable Version](https://poser.pugx.org/devgeniem/wp-cron-runner/v/unstable)](https://packagist.org/packages/devgeniem/wp-cron-runner)
[![License](https://poser.pugx.org/devgeniem/wp-cron-runner/license)](https://packagist.org/packages/devgeniem/wp-cron-runner)

This simple mu-plugin lets you run WordPress cron jobs for a site or all network sites via a single endpoint. This is useful when building a single solution to run WP cron jobs on a hosting platform for different type of WordPress installations.

## Installation

Install the plugin with Composer. It requires the [Bedrock mu-plugins autoloader](https://roots.io/bedrock/docs/mu-plugins-autoloader/).

```
composer require devgeniem/wp-cron-runner
```

Or install the plugin manually by copying the `plugin.php` under your mu-plugin directory and renaming it to `wp-cron-runner.php`.

## Usage

This plugin defines a single endpoint to run WP crons.
```
http(s)://www.mysite.com/run-cron
```

On a [network](https://codex.wordpress.org/Create_A_Network) installation you only need to request a single site. The plugin will fetch all active sites from the database and call the `/wp-cron.php` endpoint to run scheduled events.

To enable timed execution create a cronjob to make a HTTP request to the `/run-cron` endpoint. To test if the plugin is functioning, request the endpoint on a browser to see a list of sites the cron was executed for.

The plugin will exit WordPress execution after a successful request to the endpoint. This will minimize the server load since the plugin code is run when mu-plugins are loaded.

## Basic auth

To restrict access to the plugin endpoint, define the following constants to enable basic auth:

```php
// Username
define( 'WP_CRON_RUNNER_AUTH_USER', 'username' );
// Password
define( 'WP_CRON_RUNNER_AUTH_PW', 'pw' );
```

## Maintainers
[@villesiltala](https://github.com/villesiltala)

## License
GPLv3