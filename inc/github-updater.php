<?php
/**
 * GitHub Theme Updater – Öffentliches Repo
 * Funktioniert auf allen Installationen ohne Token
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/*
|--------------------------------------------------------------------------
| 🔴 KONFIGURATION – nur hier Werte anpassen
|--------------------------------------------------------------------------
*/
define( 'MYTHEME_SLUG', 'borndigital' );               // Theme-Ordnername
define( 'MYTHEME_GITHUB_OWNER', 'borndigital89' );    // GitHub Username
define( 'MYTHEME_GITHUB_REPO', 'BornDigital-Master' );// GitHub Repo Name

/*
|--------------------------------------------------------------------------
| ✅ UPDATER-KLASSE
|--------------------------------------------------------------------------
*/
class MyTheme_GitHub_Updater {

    public function __construct() {
        // Prüft auf Theme-Updates
        add_filter( 'site_transient_update_themes', [ $this, 'check_for_update' ] );
    }

    // Prüft, ob ein Update verfügbar ist
    public function check_for_update( $transient ) {

        if ( empty( $transient->checked[ MYTHEME_SLUG ] ) ) {
            return $transient;
        }

        $theme   = wp_get_theme( MYTHEME_SLUG );
        $current = ltrim( $theme->get( 'Version' ), 'v' );

        $release = $this->get_latest_release();
        if ( ! $release || empty( $release->tag_name ) ) return $transient;

        $remote_version = ltrim( $release->tag_name, 'v' );

        if ( version_compare( $current, $remote_version, '<' ) ) {

            // Browser-download URL für öffentliche Repos funktioniert direkt
            $transient->response[ MYTHEME_SLUG ] = [
                'theme'       => MYTHEME_SLUG,
                'new_version' => $remote_version,
                'url'         => $release->html_url,
                'package'     => $release->zipball_url,
            ];
        }

        return $transient;
    }

    // Holt das neueste Release von GitHub
    private function get_latest_release() {
        $url = sprintf(
            'https://api.github.com/repos/%s/%s/releases/latest',
            MYTHEME_GITHUB_OWNER,
            MYTHEME_GITHUB_REPO
        );

        $response = wp_remote_get( $url, [
            'headers' => [
                'Accept'     => 'application/vnd.github+json',
                'User-Agent' => 'WordPress Theme Updater',
            ],
            'timeout' => 20,
        ]);

        if ( is_wp_error( $response ) ) return false;
        return json_decode( wp_remote_retrieve_body( $response ) );
    }
}

// Updater starten
new MyTheme_GitHub_Updater();