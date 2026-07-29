<?php
/**
 * GitHub Theme Updater – Öffentliches Repo
 * Funktioniert auf allen Installationen ohne Token
 *
 * Änderungen gegenüber der Vorversion:
 * - Prüfung des HTTP-Status-Codes (fängt Rate-Limits/404 sauber ab)
 * - Caching der GitHub-Antwort via Transient (schont das Rate-Limit)
 * - error_log()-Meldungen bei Problemen, damit Fehler nicht mehr "still" verschwinden
 * - Fix für das ZIP-Ordner-Problem: GitHubs zipball_url erzeugt einen Ordner
 *   wie "owner-repo-<hash>", WordPress erwartet aber einen Ordner mit dem
 *   Theme-Slug – sonst wird beim Update der falsche Ordner installiert.
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
define( 'MYTHEME_GITHUB_OWNER', 'borndigital89' );      // GitHub Username
define( 'MYTHEME_GITHUB_REPO', 'BornDigital-Master' );  // GitHub Repo Name

/*
|--------------------------------------------------------------------------
| ✅ UPDATER-KLASSE
|--------------------------------------------------------------------------
*/
class MyTheme_GitHub_Updater {

    private $cache_key = 'mytheme_github_release';
    private $cache_ttl = 6 * HOUR_IN_SECONDS;   // reduziert API-Aufrufe -> weniger Rate-Limit-Probleme
    private $error_ttl = 15 * MINUTE_IN_SECONDS; // bei Fehlern kürzer cachen, damit es sich zeitnah erholt

    public function __construct() {
        add_filter( 'site_transient_update_themes', [ $this, 'check_for_update' ] );
        add_filter( 'upgrader_source_selection', [ $this, 'fix_source_folder_name' ], 10, 4 );
    }

    /**
     * Prüft, ob ein Update verfügbar ist
     */
    public function check_for_update( $transient ) {
        if ( empty( $transient->checked ) ) {
            return $transient;
        }

        $theme   = wp_get_theme( MYTHEME_SLUG );
        $current = ltrim( $theme->get( 'Version' ), 'v' );
        $release = $this->get_latest_release();

        if ( ! $release || empty( $release->tag_name ) || empty( $release->zipball_url ) ) {
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

    /**
     * Holt das neueste Release von GitHub (mit Caching + Fehlerbehandlung)
     */
    private function get_latest_release() {
        $cached = get_transient( $this->cache_key );
        if ( false !== $cached ) {
            return $cached;
        }

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
        ] );

        if ( is_wp_error( $response ) ) {
            error_log( 'MyTheme Updater: wp_remote_get Fehler – ' . $response->get_error_message() );
            set_transient( $this->cache_key, false, $this->error_ttl );
            return false;
        }

        $code = (int) wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );

        if ( 200 !== $code ) {
            // Sehr häufige Ursache für "Update erscheint nicht mehr": Rate-Limit (403)
            // oder Release ist als Draft/Pre-Release markiert (dann liefert GitHub 404).
            error_log( sprintf(
                'MyTheme Updater: GitHub API antwortete mit Status %d. Antwort: %s',
                $code,
                $body
            ) );
            set_transient( $this->cache_key, false, $this->error_ttl );
            return false;
        }

        $data = json_decode( $body );

        if ( empty( $data->tag_name ) ) {
            error_log( 'MyTheme Updater: Kein tag_name in der Antwort. Ist v' . '... als Pre-Release/Draft markiert?' );
            set_transient( $this->cache_key, false, $this->error_ttl );
            return false;
        }

        set_transient( $this->cache_key, $data, $this->cache_ttl );

        return $data;
    }

    /**
     * GitHubs zipball_url erzeugt einen Ordner wie "owner-repo-<hash>".
     * WordPress erwartet beim Update aber einen Ordner mit dem Theme-Slug
     * ("borndigital"), sonst wird das Theme in einen falschen Ordner
     * installiert bzw. das bestehende Theme wird nicht sauber überschrieben.
     */
    public function fix_source_folder_name( $source, $remote_source, $upgrader, $hook_extra ) {
        global $wp_filesystem;

        if ( empty( $hook_extra['theme'] ) || MYTHEME_SLUG !== $hook_extra['theme'] ) {
            return $source;
        }

        $corrected_source = trailingslashit( $remote_source ) . MYTHEME_SLUG . '/';

        if ( $source !== $corrected_source && $wp_filesystem ) {
            if ( $wp_filesystem->move( $source, $corrected_source, true ) ) {
                return $corrected_source;
            }
            error_log( 'MyTheme Updater: Konnte Ordner nicht umbenennen von ' . $source . ' nach ' . $corrected_source );
        }

        return $source;
    }
}

// Updater starten
new MyTheme_GitHub_Updater();

?>