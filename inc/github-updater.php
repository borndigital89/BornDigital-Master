<?php
/**
 * GitHub Theme Updater – Private Repo (Releases API)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/*
|--------------------------------------------------------------------------
| 🔴 HIER MUSST DU SELBST ETWAS ÄNDERN
|--------------------------------------------------------------------------
*/

/**
 * 🔴 Theme-Ordnername
 */
define( 'MYTHEME_SLUG', 'borndigital' );

/**
 * 🔴 GitHub Repo Owner
 */
define( 'MYTHEME_GITHUB_OWNER', 'borndigital89' );

/**
 * 🔴 GitHub Repo Name
 */
define( 'MYTHEME_GITHUB_REPO', 'BornDigital-Master' );

/**
 * 🔴 GitHub Token (empfohlen in wp-config.php)
 */
if ( ! defined( 'MYTHEME_GITHUB_TOKEN' ) ) {
    return;
}

/*
|--------------------------------------------------------------------------
| ❌ AB HIER NICHTS MEHR ÄNDERN
|--------------------------------------------------------------------------
*/

class MyTheme_GitHub_Updater {

    public function __construct() {

        add_filter( 'site_transient_update_themes', [ $this, 'check_for_update' ] );
        add_filter( 'upgrader_pre_download', [ $this, 'auth_download' ], 10, 3 );
        add_filter( 'upgrader_post_install', [ $this, 'fix_directory' ], 10, 3 );
    }

    public function check_for_update( $transient ) {

        if ( empty( $transient->checked[ MYTHEME_SLUG ] ) ) {
            return $transient;
        }

        $theme   = wp_get_theme( MYTHEME_SLUG );
        $current = ltrim( $theme->get( 'Version' ), 'v' );

        $release = $this->get_latest_release();

        if ( ! $release || empty( $release->tag_name ) ) {
            return $transient;
        }

        $remote_version = ltrim( $release->tag_name, 'v' );

        if ( version_compare( $current, $remote_version, '<' ) ) {

            $transient->response[ MYTHEME_SLUG ] = [
                'theme'       => MYTHEME_SLUG,
                'new_version' => $remote_version,
                'url'         => $release->html_url,
                'package'     => $release->zipball_url,
            ];
        }

        return $transient;
    }

    public function auth_download( $reply, $package, $upgrader ) {

        if ( strpos( $package, 'api.github.com/repos/' . MYTHEME_GITHUB_OWNER ) === false ) {
            return $reply;
        }

        $response = wp_remote_get( $package, [
            'headers' => [
                'Authorization' => 'token ' . MYTHEME_GITHUB_TOKEN,
                'User-Agent'    => 'WordPress Theme Updater'
            ],
            'timeout' => 30
        ] );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        return wp_remote_retrieve_body( $response );
    }

    public function fix_directory( $response, $hook_extra, $result ) {

        if (
            empty( $hook_extra['theme'] ) ||
            $hook_extra['theme'] !== MYTHEME_SLUG
        ) {
            return $response;
        }

        global $wp_filesystem;

        $correct_path = WP_CONTENT_DIR . '/themes/' . MYTHEME_SLUG;

        if ( $wp_filesystem->is_dir( $result['destination'] ) ) {
            $wp_filesystem->move( $result['destination'], $correct_path );
            $result['destination'] = $correct_path;
        }

        return $result;
    }

    private function get_latest_release() {

        $request = wp_remote_get(
            sprintf(
                'https://api.github.com/repos/%s/%s/releases/latest',
                MYTHEME_GITHUB_OWNER,
                MYTHEME_GITHUB_REPO
            ),
            [
                'headers' => [
                    'Authorization' => 'token ' . MYTHEME_GITHUB_TOKEN,
                    'Accept'        => 'application/vnd.github+json',
                    'User-Agent'    => 'WordPress Theme Updater'
                ],
                'timeout' => 15
            ]
        );

        if ( is_wp_error( $request ) ) {
            return false;
        }

        return json_decode( wp_remote_retrieve_body( $request ) );
    }
}

new MyTheme_GitHub_Updater();
