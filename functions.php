<?php
/**
 * Load jquery
 */
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_script('jquery');
});


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


/**
 * WP Block Animate (Fixed Editor)
 * Description: Adds Animate.css options to Gutenberg blocks. Robust loading (uses proper editor script dependencies).
 * Version: 1.0.2
 * Author: ChatGPT
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enqueue animate.css on the frontend.
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
 * Enqueue editor assets. The editor script is a separate file and registered with proper WP dependencies
 * so the editor globals (wp.blockEditor / wp.editor, wp.hooks, etc.) are available when it runs.
 */
function wba_enqueue_editor_assets() {
    wp_enqueue_style(
        'wba-animate-css',
        'https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css',
        array(),
        '4.1.1'
    );

    // 1. URL für den Browser
    $dir_uri = get_stylesheet_directory_uri() . '/assets/js/';
    // 2. Absoluter Server-Pfad für filemtime (wichtig!)
    $dir_path = get_stylesheet_directory() . '/assets/js/';
    $file = 'editor.js';

    wp_register_script(
        'wba-editor',
        $dir_uri . $file,
        array( 'wp-hooks', 'wp-blocks', 'wp-element', 'wp-components', 'wp-compose', 'wp-block-editor' ),
        file_exists($dir_path . $file) ? filemtime($dir_path . $file) : '1.0', 
        true
    );

    wp_enqueue_script( 'wba-editor' );
}
add_action( 'enqueue_block_editor_assets', 'wba_enqueue_editor_assets' );
/*
// Frontend-Script für "nur bei Sichtbarkeit"
add_action( 'wp_enqueue_scripts', function() {
    wp_add_inline_script( 'jquery-core', "
        document.addEventListener('DOMContentLoaded', function(){
            const items = document.querySelectorAll('.wba-observe');
            if ('IntersectionObserver' in window) {
                let observer = new IntersectionObserver((entries)=>{
                    entries.forEach(entry=>{
                        if (entry.isIntersecting) {
                            entry.target.classList.add('wba-visible');
                            observer.unobserve(entry.target);
                        }
                    });
                }, {threshold:0.2});
                items.forEach(el=>{ observer.observe(el); });
            } else {
                items.forEach(el=>el.classList.add('wba-visible'));
            }
        });
    ");
    wp_add_inline_style( 'wba-animate-css', '.wba-observe{opacity:0;} .wba-observe.wba-visible{opacity:1;}' );
});
*/

// Animate.css erst starten, wenn im Viewport
function mytheme_enqueue_animate_on_scroll() {
    wp_add_inline_script(
        'jquery-core',
        "
        document.addEventListener('DOMContentLoaded', () => {
          const elements = document.querySelectorAll('.animate__animated');

          // Welche Klassen sind reine Animationsnamen (nicht Utilities)?
          const isAnimNameClass = (c) =>
            c.startsWith('animate__') &&
            c !== 'animate__animated' &&
            !/^animate__delay-/.test(c) &&
            !/^animate__repeat-/.test(c) &&
            !['animate__infinite','animate__slower','animate__faster','animate__slow','animate__fast'].includes(c);

          const observer = new IntersectionObserver((entries, obs) => {
            entries.forEach(entry => {
              if (entry.isIntersecting) {
                const el = entry.target;
                const toAdd = el.dataset.animateCss || '';
                if (toAdd) {
                  toAdd.split(' ').forEach(cls => cls && el.classList.add(cls));
                }
                // sichtbar machen
                el.style.visibility = '';
                el.style.opacity = '';
                obs.unobserve(el); // nur einmal abspielen; entferne diese Zeile für wiederholtes Abspielen
              }
            });
          }, { threshold: 0.2 });

          elements.forEach(el => {
            // vorhandene Animationsklassen merken und entfernen, damit sie NICHT sofort starten
            const animClasses = Array.from(el.classList).filter(isAnimNameClass);
            if (animClasses.length) {
              el.dataset.animateCss = animClasses.join(' ');
              animClasses.forEach(cls => el.classList.remove(cls));
            }
            // bis zum Start unsichtbar halten (keine Animation im Hintergrund)
            el.style.visibility = 'hidden';
            el.style.opacity = '0';
            observer.observe(el);
          });
        });
        "
    );
}
add_action('wp_enqueue_scripts', 'mytheme_enqueue_animate_on_scroll');




?>