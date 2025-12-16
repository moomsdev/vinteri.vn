<?php
/**
 * Cache Helper Functions
 * Wrapper functions để dễ sử dụng caching
 */

use App\Settings\MMSTools\CacheHelper;
use App\Settings\MMSTools\QueryOptimizer;

if (!function_exists('get_cached_query')) {
    /**
     * Get cached WP_Query results
     * 
     * @param string $key Cache key
     * @param array $args WP_Query args
     * @param int $expiration Expiration time (seconds)
     * @return array Query results
     */
    function get_cached_query($key, $args, $expiration = 3600) {
        return CacheHelper::get_cached_query($key, $args, $expiration);
    }
}

if (!function_exists('get_cached_data')) {
    /**
     * Get cached data từ callback
     * 
     * @param string $key Cache key
     * @param callable $callback Function to get data
     * @param int $expiration Expiration time (seconds)
     * @return mixed Cached data
     */
    function get_cached_data($key, $callback, $expiration = 3600) {
        return CacheHelper::get_cached_data($key, $callback, $expiration);
    }
}

if (!function_exists('cached_fragment')) {
    /**
     * Output cached HTML fragment
     * 
     * @param string $key Cache key
     * @param callable $callback Function to render HTML
     * @param int $expiration Expiration time (seconds)
     * @return void
     */
    function cached_fragment($key, $callback, $expiration = 3600) {
        CacheHelper::cached_fragment($key, $callback, $expiration);
    }
}

if (!function_exists('clear_theme_cache')) {
    /**
     * Clear cache by pattern
     * 
     * @param string $pattern Cache key pattern (use * for wildcard)
     * @return void
     */
    function clear_theme_cache($pattern = '*') {
        CacheHelper::clear_cache($pattern);
    }
}

if (!function_exists('get_optimized_posts')) {
    /**
     * Get optimized posts với cache priming
     * 
     * @param array $args WP_Query args
     * @param bool $prime_meta Prime meta cache
     * @param bool $prime_terms Prime terms cache
     * @return array Posts
     */
    function get_optimized_posts($args, $prime_meta = true, $prime_terms = true) {
        return QueryOptimizer::get_posts_optimized($args, $prime_meta, $prime_terms);
    }
}

if (!function_exists('get_related_posts')) {
    /**
     * Get related posts (cached)
     * 
     * @param int $post_id Post ID
     * @param int $limit Number of posts
     * @return array Related posts
     */
    function get_related_posts($post_id, $limit = 5) {
        return QueryOptimizer::get_related_posts($post_id, $limit);
    }
}

if (!function_exists('prime_posts_cache')) {
    /**
     * Prime cache cho array of posts
     * 
     * @param array $posts Array of WP_Post objects
     * @return void
     */
    function prime_posts_cache($posts) {
        if (empty($posts)) {
            return;
        }
        
        $post_ids = wp_list_pluck($posts, 'ID');
        
        QueryOptimizer::prime_post_meta_cache($post_ids);
        QueryOptimizer::prime_term_cache($post_ids);
    }
}

