<?php
/**
 * Plugin Name: Bricks Zero-Plugin Standard Media Sync
 * Description: Centralizes the core WordPress Media Library to the main site (Blog ID 1) across a Multisite network.
 * Version: 1.0.0
 * Author: M Nudin Lubis
 * License: GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * 5. Shared Media Library (Centralized Media)
 * Forces the core WP Media Library from all subsites to refer to the Main Site (Blog ID 1).
 */
if ( is_multisite() ) {
    
    // Switch to main site for media queries and uploads
    add_action( 'wp_ajax_query-attachments', 'switch_to_main_site_media', 0 );
    add_action( 'wp_ajax_save-attachment', 'switch_to_main_site_media', 0 );
    add_action( 'wp_ajax_image-editor', 'switch_to_main_site_media', 0 );

    function switch_to_main_site_media() {
        if ( get_current_blog_id() !== 1 ) {
            switch_to_blog( 1 );
        }
    }

    // Fix attachment URLs to ensure they point to the main domain
    add_filter( 'wp_get_attachment_url', 'fix_multisite_attachment_url', 10, 2 );
    function fix_multisite_attachment_url( $url, $post_id ) {
        if ( get_current_blog_id() !== 1 ) {
            switch_to_blog( 1 );
            $url = wp_get_attachment_url( $post_id );
            restore_current_blog();
        }
        return $url;
    }
    
    // Restore context after AJAX actions are complete
    add_action( 'wp_ajax_query-attachments', 'restore_current_site_context', 999 );
    add_action( 'wp_ajax_save-attachment', 'restore_current_site_context', 999 );
    add_action( 'wp_ajax_image-editor', 'restore_current_site_context', 999 );
    
    function restore_current_site_context() {
        if ( ms_is_switched() ) {
            restore_current_blog();
        }
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
