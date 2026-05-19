<?php
/**
 * Plugin Name: Bricks Zero-Plugin Multisite Media & HappyFiles Sync
 * Description: Centralizes the WordPress Media Library and HappyFiles folders to the main site (Blog ID 1) across a Multisite network.
 * Version: 1.0.0
 * Author: M Nudin Lubis
 * License: GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * RE-OPTIMIZED: CENTRALIZED MEDIA LIBRARY + HAPPYFILES COMPATIBILITY
 * Forces Media & HappyFiles Folders from all subsites to refer to the Main Site (Blog ID 1)
 */
if ( is_multisite() ) {

    // 1. Primary Route: Secure core WordPress AJAX & HappyFiles hooks
    $media_and_hf_actions = [
        'query-attachments',
        'save-attachment',
        'image-editor',
        'insert_into_project',
        // HappyFiles-specific AJAX hooks
        'happyfiles_get_terms',
        'happyfiles_save_term',
        'happyfiles_delete_term',
        'happyfiles_sort_terms'
    ];

    foreach ( $media_and_hf_actions as $action ) {
        add_action( 'wp_ajax_' . $action, 'switch_to_main_site_media_context', 0 );
    }

    // 2. Fallback Route: Dynamically intercept any other HappyFiles-related AJAX
    add_action( 'admin_init', function() {
        if ( defined( 'DOING_AJAX' ) && DOING_AJAX && isset( $_REQUEST['action'] ) ) {
            if ( strpos( $_REQUEST['action'], 'happyfiles_' ) === 0 && get_current_blog_id() !== 1 ) {
                switch_to_blog( 1 );
            }
        }
    }, 0 );

    // 3. REST API Route: Modern HappyFiles versions use the REST API to render folder sidebars
    add_action( 'rest_api_init', function() {
        if ( get_current_blog_id() !== 1 && isset( $_SERVER['REQUEST_URI'] ) ) {
            if ( strpos( $_SERVER['REQUEST_URI'], '/happyfiles/' ) !== false ) {
                switch_to_blog( 1 );
            }
        }
    }, 0 );

    // Context-switching executor function to swap to Blog ID 1
    function switch_to_main_site_media_context() {
        if ( get_current_blog_id() !== 1 ) {
            switch_to_blog( 1 );
        }
    }

    // 4. URL Fixer: Ensures image source paths always point securely to the main domain
    add_filter( 'wp_get_attachment_url', 'fix_multisite_attachment_url_happyfiles', 10, 2 );
    function fix_multisite_attachment_url_happyfiles( $url, $post_id ) {
        if ( get_current_blog_id() !== 1 ) {
            switch_to_blog( 1 );
            $url = wp_get_attachment_url( $post_id );
            restore_current_blog();
        }
        return $url;
    }
}

// ========================================================================
    // BRICKS BUILDER RENDER FIX
    // Forces Bricks Builder to fetch URL, Srcset, and Alt text from the Main Site (Blog ID 1)
    // ========================================================================

    // 1. Fix wp_get_attachment_image_src (Used by Bricks to render the image src attribute)
    add_filter( 'wp_get_attachment_image_src', 'bricks_fix_multisite_image_src', 10, 4 );
    function bricks_fix_multisite_image_src( $image, $attachment_id, $size, $icon ) {
        if ( get_current_blog_id() !== 1 ) {
            // Temporarily remove the filter to prevent infinite loops
            remove_filter( 'wp_get_attachment_image_src', 'bricks_fix_multisite_image_src', 10 );
            
            switch_to_blog( 1 );
            $image = wp_get_attachment_image_src( $attachment_id, $size, $icon );
            restore_current_blog();
            
            // Restore the filter
            add_filter( 'wp_get_attachment_image_src', 'bricks_fix_multisite_image_src', 10, 4 );
        }
        return $image;
    }

    // 2. Fix wp_get_attachment_metadata (Used by Bricks for responsive Srcset & Lazyload generation)
    add_filter( 'wp_get_attachment_metadata', 'bricks_fix_multisite_image_meta', 10, 2 );
    function bricks_fix_multisite_image_meta( $data, $post_id ) {
        if ( get_current_blog_id() !== 1 ) {
            remove_filter( 'wp_get_attachment_metadata', 'bricks_fix_multisite_image_meta', 10 );
            
            switch_to_blog( 1 );
            $data = wp_get_attachment_metadata( $post_id );
            restore_current_blog();
            
            add_filter( 'wp_get_attachment_metadata', 'bricks_fix_multisite_image_meta', 10, 2 );
        }
        return $data;
    }

    // 3. Fix ALT text fallback (Ensures the alt text from the main media library is fetched)
    add_filter( 'get_post_metadata', 'bricks_fix_multisite_image_alt', 10, 4 );
    function bricks_fix_multisite_image_alt( $value, $object_id, $meta_key, $single ) {
        if ( '_wp_attachment_image_alt' === $meta_key && get_current_blog_id() !== 1 ) {
            remove_filter( 'get_post_metadata', 'bricks_fix_multisite_image_alt', 10 );
            
            switch_to_blog( 1 );
            $value = get_post_meta( $object_id, $meta_key, $single );
            restore_current_blog();
            
            add_filter( 'get_post_metadata', 'bricks_fix_multisite_image_alt', 10, 4 );
        }
        return $value;
    }
