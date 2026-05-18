<?php
/**
 * Title: Bricks Zero-Plugin Multilingual Engine
 * Description: Core PHP functions for dynamic language routing and structural Hreflang SEO injection.
 * Repository: bricks-zero-plugin-multilingual
 */

/**
 * 1. DYNAMIC LANGUAGE SWITCHER LOGIC
 * Automatically determines the target URL for the language switcher element.
 * * @return string Target translation URL or fallback homepage URL.
 */
function get_language_switcher_url() {
    $current_blog_id = get_current_blog_id();
    
    // Define your production or staging domains here
    $main_domain = 'https://domain.com';
    $sub_domain  = 'https://domain.com/zh';
    
    // Assign the fallback homepage URL depending on the current active site
    if ( $current_blog_id == 1 ) {
        $target_home_url = $sub_domain;
    } else {
        $target_home_url = $main_domain;
    }

    // A. Homepage routing Optimization (Zero Database Queries)
    if ( is_front_page() || is_home() ) {
        return esc_url( $target_home_url );
    }

    // B. Query the mapped Custom Field URL
    $post_id = get_the_ID();
    $translated_url = get_post_meta( $post_id, 'translated_url', true );
    
    if ( ! empty( $translated_url ) ) {
        return esc_url( $translated_url );
    }
    
    // C. Fallback Route: Direct to the target site homepage if the page has no translation mapped
    return esc_url( $target_home_url );
}

/**
 * 2. BRICKS BUILDER ECHO FUNCTION WHITELIST
 * Authorizes the custom switcher function to bypass Bricks' built-in remote code execution (RCE) protection.
 */
add_filter( 'bricks/code/echo_function_names', function() {
    return [
        'get_language_switcher_url',
    ];
} );

/**
 * 3. TECHNICAL SEO HREFLANG INJECTION
 * Inject standard `<link rel="alternate">` tags into the document <head> 
 * to notify search engine crawlers of the relationship between languages.
 */
add_action( 'wp_head', 'inject_custom_hreflang' );

function inject_custom_hreflang() {
    if ( is_singular() ) {
        $translated_url = get_post_meta( get_the_ID(), 'translated_url', true );
        
        if ( ! empty( $translated_url ) ) {
            $current_url = home_url();
            
            // Language detection rule based on the sub-directory string path
            if ( strpos( $current_url, '/zh' ) !== false ) {
                // Executing inside the Chinese Sub-site
                echo '<link rel="alternate" hreflang="en" href="' . esc_url( $translated_url ) . '" />' . "\n";
                echo '<link rel="alternate" hreflang="zh-Hans" href="' . esc_url( get_permalink() ) . '" />' . "\n";
            } else {
                // Executing inside the Primary English Site
                echo '<link rel="alternate" hreflang="zh-Hans" href="' . esc_url( $translated_url ) . '" />' . "\n";
                echo '<link rel="alternate" hreflang="en" href="' . esc_url( get_permalink() ) . '" />' . "\n";
            }
        }
    }
}
