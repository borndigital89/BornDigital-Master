<?php
/**
 * Load jquery
 */
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_script('jquery');
});

/**
 * Github updater
 *
 */
require_once get_template_directory() . '/inc/github-updater.php';


/**
 * Register shortcode for current Year.
 *
 * @since 1.0.0
 */
function current_year() {
    $year = date('Y');
    return $year;
}

add_shortcode('year', 'current_year');

/* SVG Logos zulassen */
function allow_svg_uploads( $mimes ) {
    $mimes['svg'] = 'image/svg+xml';
    return $mimes;
}
add_filter( 'upload_mimes', 'allow_svg_uploads' );

/**
 * Register shortcodes for ACF Fields.
 *
 * @since 1.0.0
 */
add_shortcode('acf', function ($atts) {

    $atts = shortcode_atts([
        'field'   => '',
        'post_id' => get_the_ID(),
    ], $atts);

    if (empty($atts['field'])) {
        return '';
    }

    if (!function_exists('get_field')) {
        return '';
    }

    $value = get_field($atts['field'], $atts['post_id']);

    if (empty($value)) {
        return '';
    }

    // WYSIWYG & Text sauber ausgeben
    return wp_kses_post($value);
});


/*
Animate
*/
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Animate.css auf Frontend laden
 */
function wba_enqueue_frontend() {
    wp_enqueue_style(
        'wba-animate-css',
        'https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css',
        array(),
        '4.1.1'
    );
}
add_action( 'wp_enqueue_scripts', 'wba_enqueue_frontend' );

/**
 * Editor assets laden
 */
function wba_enqueue_editor_assets() {
    wp_enqueue_style(
        'wba-animate-css',
        'https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css',
        [],
        '4.1.1'
    );

    $file = 'editor.js';
    $dir_uri  = get_template_directory_uri() . '/assets/js/';
    $dir_path = get_template_directory() . '/assets/js/';

    if ( ! file_exists( $dir_path . $file ) ) return;

    wp_register_script(
        'wba-editor',
        $dir_uri . $file,
        [
            'wp-hooks',
            'wp-blocks',
            'wp-element',
            'wp-components',
            'wp-compose',
            'wp-block-editor',
        ],
        filemtime( $dir_path . $file ),
        true
    );

    wp_enqueue_script( 'wba-editor' );
}
add_action( 'enqueue_block_editor_assets', 'wba_enqueue_editor_assets' );


/**
 * Inline JS Observer für „nur starten, wenn sichtbar“
 */
function wba_enqueue_animate_on_scroll() {
    wp_add_inline_script(
        'jquery-core', // Tipp: Nutze 'wp-dom-ready' falls vorhanden, sonst ok
        "
        document.addEventListener('DOMContentLoaded', function(){
            var elements = document.querySelectorAll('.wba-observe');
            
            var observer = new IntersectionObserver(function(entries, obs){
                entries.forEach(function(entry){
                    if(entry.isIntersecting){
                        var el = entry.target;
                        var anim = el.getAttribute('data-wba-anim');
                        
                        if(anim){
                            el.classList.add('animate__animated', 'animate__' + anim);
                        }

                        el.style.visibility = 'visible';
                        el.style.opacity = '1';
                        obs.unobserve(el);
                    }
                });
            }, { threshold: 0.1 });

            elements.forEach(function(el){
                el.style.visibility = 'hidden';
                el.style.opacity = '0';
                observer.observe(el);
            });
        });
        "
    );
}

add_action('wp_enqueue_scripts','wba_enqueue_animate_on_scroll');


/**
 * Custom Login Logo sicher und standardkonform einbinden
 */
function mastertheme_login_logo() { 
    // Sicherstellen, dass das Logo im Theme existiert (child-theme-sicher via get_theme_file_uri)
    $logo_url = get_theme_file_uri('/assets/images/login-logo.svg');
    ?>
    <style type="text/css">
        #login h1 a, .login h1 a {
            background-image: url('<?php echo esc_url($logo_url); ?>') !important;
            height: 80px; /* Höhe des Logos flexibel anpassbar */
            width: 320px; /* Breite des Logos flexibel anpassbar */
            background-size: contain !important;
            background-position: center center !important;
            padding-bottom: 20px;
        }
    </style>
    <?php 
}
add_action('login_enqueue_scripts', 'mastertheme_login_logo');

/**
 * Link des Login-Logos auf die Startseite der Website ändern
 * 
 * @return string Die URL der Startseite
 */
function mastertheme_login_logo_url() {
    return esc_url(home_url('/'));
}
add_filter('login_headerurl', 'mastertheme_login_logo_url');

/**
 * Tooltip-Text (Title-Attribut) des Login-Logos ändern
 * 
 * @param string $headertext Der standardmäßige Headertext
 * @return string Der bereinigte Seitenname für das HTML-Attribut
 */
function mastertheme_login_logo_url_title($headertext) {
    // esc_attr() verhindert kaputtes HTML, falls der Seitenname Sonderzeichen enthält
    return esc_attr(get_bloginfo('name'));
}
add_filter('login_headertext', 'mastertheme_login_logo_url_title');

