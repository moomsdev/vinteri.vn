<?php

/**
 * SEO Helper Functions
 * Provides meta description, Open Graph tags, and Schema.org structured data
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Generate dynamic meta description
 */
function mms_meta_description() {
    if (is_singular()) {
        $post = get_queried_object();
        
        // Try excerpt first, then content
        if (!empty($post->post_excerpt)) {
            $desc = $post->post_excerpt;
        } else {
            $desc = wp_trim_words(strip_tags($post->post_content), 30, '...');
        }
    } elseif (is_category() || is_tag() || is_tax()) {
        $term = get_queried_object();
        $desc = !empty($term->description) 
            ? $term->description 
            : get_bloginfo('description');
    } elseif (is_home() || is_front_page()) {
        $desc = get_bloginfo('description');
    } else {
        $desc = get_bloginfo('description');
    }
    
    echo '<meta name="description" content="' . esc_attr(wp_strip_all_tags($desc)) . '">' . "\n";
}

/**
 * Add Open Graph and Twitter Card meta tags
 */
function mms_add_open_graph_tags() {
    if (is_singular()) {
        global $post;
        
        $title = get_the_title();
        $excerpt = get_the_excerpt();
        if (empty($excerpt)) {
            $excerpt = wp_trim_words(strip_tags($post->post_content), 30, '...');
        }
        $url = get_permalink();
        $site_name = get_bloginfo('name');
        
        // Open Graph tags
        echo '<meta property="og:type" content="article">' . "\n";
        echo '<meta property="og:title" content="' . esc_attr($title) . '">' . "\n";
        echo '<meta property="og:description" content="' . esc_attr(wp_strip_all_tags($excerpt)) . '">' . "\n";
        echo '<meta property="og:url" content="' . esc_url($url) . '">' . "\n";
        echo '<meta property="og:site_name" content="' . esc_attr($site_name) . '">' . "\n";
        
        if (has_post_thumbnail()) {
            $image_url = get_the_post_thumbnail_url(null, 'large');
            echo '<meta property="og:image" content="' . esc_url($image_url) . '">' . "\n";
            
            // Get image dimensions for better display
            $image_id = get_post_thumbnail_id();
            $image_meta = wp_get_attachment_metadata($image_id);
            if (!empty($image_meta['width']) && !empty($image_meta['height'])) {
                echo '<meta property="og:image:width" content="' . esc_attr($image_meta['width']) . '">' . "\n";
                echo '<meta property="og:image:height" content="' . esc_attr($image_meta['height']) . '">' . "\n";
            }
        }
        
        // Article specific tags
        echo '<meta property="article:published_time" content="' . esc_attr(get_the_date('c')) . '">' . "\n";
        echo '<meta property="article:modified_time" content="' . esc_attr(get_the_modified_date('c')) . '">' . "\n";
        
        // Twitter Card tags
        echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
        echo '<meta name="twitter:title" content="' . esc_attr($title) . '">' . "\n";
        echo '<meta name="twitter:description" content="' . esc_attr(wp_strip_all_tags($excerpt)) . '">' . "\n";
        
        if (has_post_thumbnail()) {
            echo '<meta name="twitter:image" content="' . esc_url(get_the_post_thumbnail_url(null, 'large')) . '">' . "\n";
        }
        
    } elseif (is_home() || is_front_page()) {
        // Homepage Open Graph
        echo '<meta property="og:type" content="website">' . "\n";
        echo '<meta property="og:title" content="' . esc_attr(get_bloginfo('name')) . '">' . "\n";
        echo '<meta property="og:description" content="' . esc_attr(get_bloginfo('description')) . '">' . "\n";
        echo '<meta property="og:url" content="' . esc_url(home_url('/')) . '">' . "\n";
        echo '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '">' . "\n";
    }
}
add_action('wp_head', 'mms_add_open_graph_tags', 5);

/**
 * Add JSON-LD Schema.org structured data
 */
function mms_add_schema_markup() {
    if (!is_singular()) {
        return;
    }
    
    global $post;
    
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => get_the_title(),
        'datePublished' => get_the_date('c'),
        'dateModified' => get_the_modified_date('c'),
        'author' => [
            '@type' => 'Person',
            'name' => get_the_author(),
            'url' => get_author_posts_url(get_the_author_meta('ID')),
        ],
        'publisher' => [
            '@type' => 'Organization',
            'name' => get_bloginfo('name'),
            'url' => home_url('/'),
        ],
    ];
    
    // Add description
    $excerpt = get_the_excerpt();
    if (!empty($excerpt)) {
        $schema['description'] = wp_strip_all_tags($excerpt);
    } else {
        $schema['description'] = wp_trim_words(strip_tags($post->post_content), 30, '...');
    }
    
    // Add main URL
    $schema['mainEntityOfPage'] = [
        '@type' => 'WebPage',
        '@id' => get_permalink(),
    ];
    
    // Add image if available
    if (has_post_thumbnail()) {
        $image_url = get_the_post_thumbnail_url(null, 'large');
        $image_id = get_post_thumbnail_id();
        $image_meta = wp_get_attachment_metadata($image_id);
        
        $schema['image'] = [
            '@type' => 'ImageObject',
            'url' => $image_url,
        ];
        
        if (!empty($image_meta['width']) && !empty($image_meta['height'])) {
            $schema['image']['width'] = $image_meta['width'];
            $schema['image']['height'] = $image_meta['height'];
        }
    }
    
    // Add logo to publisher if site icon exists
    if (has_site_icon()) {
        $schema['publisher']['logo'] = [
            '@type' => 'ImageObject',
            'url' => get_site_icon_url(),
        ];
    }
    
    echo '<script type="application/ld+json">' . "\n";
    echo wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n";
    echo '</script>' . "\n";
}
add_action('wp_head', 'mms_add_schema_markup', 10);

/**
 * Add canonical URL
 */
function mms_add_canonical_url() {
    $canonical_url = '';
    
    if (is_singular()) {
        $canonical_url = get_permalink();
    } elseif (is_category() || is_tag() || is_tax()) {
        $term = get_queried_object();
        $canonical_url = get_term_link($term);
    } elseif (is_home() || is_front_page()) {
        $canonical_url = home_url('/');
    } elseif (is_author()) {
        $canonical_url = get_author_posts_url(get_queried_object_id());
    } elseif (is_archive()) {
        $canonical_url = get_permalink();
    }
    
    if (!empty($canonical_url) && !is_wp_error($canonical_url)) {
        echo '<link rel="canonical" href="' . esc_url($canonical_url) . '">' . "\n";
    }
}
add_action('wp_head', 'mms_add_canonical_url', 1);

