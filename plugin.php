<?php
/**
 * Plugin Name:  Cron Runner
 * Description:  This mu-plugin lets you run WP cron for a site / a network site via a single endpoint.
 * Version:      1.0.0
 * Author:       Ville Siltala / Geniem Oy
 * Author URI:   https://www.geniem.fi/
 * License:      MIT License
 */

namespace Geniem;

use WP_Error;

// We would use filter_input() for this,
// but it has a known bug on some PHP versions.
// See: https://bugs.php.net/bug.php?id=49184
$request_uri = $_SERVER['REQUEST_URI'];

// Remove the trailing forward slash.
$request_uri = rtrim( $request_uri, '/' );

// Execute our plugin only on exact url match.
if ( $request_uri === '/run-cron' ) {

    // Basic auth might be needed.
    require_auth();

    $cron_excecuted = [];
    $scheme         = defined( 'REQUEST_SCHEME' ) ? REQUEST_SCHEME : 'https';

    if ( defined( 'WP_ALLOW_MULTISITE' ) && WP_ALLOW_MULTISITE === true ) {
        global $wpdb;
        $sql = $wpdb->prepare( "SELECT domain FROM $wpdb->blogs WHERE archived='0' AND deleted ='0' LIMIT 0,300", '' );

        $results = $wpdb->get_results( $sql );

        if ( is_wp_error( $results ) ) {
            // A database error occurred.
            wp_die(
                $results->get_error_message(),
                'WP Cron Runner',
                [ 'response' => 500 ]
            );
        } elseif ( ! empty( $results ) ) {
            foreach ( $results as $blog ) {

                if ( empty( $blog->domain ) ) {
                    // Skip invalid data.
                    continue;
                }

                $home_url = $scheme . '://' . $blog->domain;
                run_cron( $home_url );
                $cron_excecuted[] = $home_url;
            }
        }
    } else {
        $home_url = get_home_url( null, '', $scheme );
        run_cron( $home_url );
        $cron_excecuted[] = $home_url;
    }

    ob_start();
    ?>
    <h1>WP Cron Runner executed for sites:</h1>
    <?php
    if ( ! empty( $cron_excecuted ) ) {
        echo '<ul>';
        foreach ( $cron_excecuted as $exc_url ) {
            echo '<li>' . esc_url( $exc_url ) . '</li>';
        }
        echo '</ul>';
    } else {
        echo '<strong>0 sites.</strong>';
    }
    ?>
    <?php
    // End the PHP process.
    wp_die(
        ob_get_clean(),
        'WP Cron Runner',
        [ 'response' => 200 ]
    );

} // End if().

/**
 * Excecutes a wp_remote_get() call to run WP cron for a specific site.
 *
 * @param string $home_url The full site url: https://www.example.com.
 * @return WP_Error|array The response or WP_Error on failure.
 */
function run_cron( $home_url ) {
    global $wp_version;

    $args = array(
        'timeout'     => 10,
        'user-agent'  => 'WordPress/' . $wp_version . '; ' . home_url(),
        'blocking'    => true, // TODO
        'sslverify'   => false,
    );

    $user = defined( 'WP_CRON_RUNNER_AUTH_USER' ) ? WP_CRON_RUNNER_AUTH_USER : null;
    $pw   = defined( 'WP_CRON_RUNNER_AUTH_PW' ) ? WP_CRON_RUNNER_AUTH_PW : null;

    if ( $user === null || $pw === null ) {

        // Set basic auth.
        $args['headers']['Authorization'] = 'Basic ' . base64_encode( $user . ':' . $pw );
    }

    $cron_url = $home_url . '/wp-cron.php';
    $response = wp_remote_get( $cron_url, $args );
    return $response;
}

/**
 * PHP basic auth checker for the plugin.
 */
function require_auth() {
    $user = defined( 'WP_CRON_RUNNER_AUTH_USER' ) ? WP_CRON_RUNNER_AUTH_USER : null;
    $pw   = defined( 'WP_CRON_RUNNER_AUTH_PW' ) ? WP_CRON_RUNNER_AUTH_PW : null;

    if ( $user === null || $pw === null ) {
        // No auth.
        return;
    }

    $not_authenticated = (
        empty( $_SERVER['PHP_AUTH_USER'] ) ||
        empty( $_SERVER['PHP_AUTH_PW'] ) ||
        $_SERVER['PHP_AUTH_USER'] !== $user ||
        $_SERVER['PHP_AUTH_PW'] !== $pw
    );

    if ( $not_authenticated ) {
        header( 'HTTP/1.1 401 Authorization Required' );
        header( 'WWW-Authenticate: Basic realm="Access denied"' );
        wp_die(
            'Access denied',
            'WP Cron Runner',
            [ 'response' => 401 ]
        );
    }
}
