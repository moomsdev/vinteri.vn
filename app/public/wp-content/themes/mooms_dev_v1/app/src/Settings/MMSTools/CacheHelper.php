<?php

namespace App\Settings\MMSTools;

/**
 * Cache Helper - Transient caching utilities
 * Giúp cache kết quả queries và giảm tải database
 */
class CacheHelper
{
    /**
     * Get cached query với transient
     * 
     * @param string $key Cache key
     * @param array $query_args WP_Query arguments
     * @param int $expiration Cache expiration (seconds)
     * @return array Query results
     */
    public static function get_cached_query($key, $query_args, $expiration = 3600)
    {
        $cached = get_transient($key);
        
        if (false === $cached) {
            $query = new \WP_Query($query_args);
            $cached = [
                'posts' => $query->posts,
                'found_posts' => $query->found_posts,
                'max_num_pages' => $query->max_num_pages
            ];
            
            set_transient($key, $cached, $expiration);
        }
        
        return $cached;
    }

    /**
     * Get cached posts với custom query
     * 
     * @param string $key Cache key
     * @param callable $callback Function trả về data
     * @param int $expiration Cache expiration (seconds)
     * @return mixed Query results
     */
    public static function get_cached_data($key, $callback, $expiration = 3600)
    {
        $cached = get_transient($key);
        
        if (false === $cached) {
            $cached = call_user_func($callback);
            set_transient($key, $cached, $expiration);
        }
        
        return $cached;
    }

    /**
     * Cache fragment (HTML output)
     * 
     * @param string $key Cache key
     * @param callable $callback Function render HTML
     * @param int $expiration Cache expiration (seconds)
     * @return void
     */
    public static function cached_fragment($key, $callback, $expiration = 3600)
    {
        $cached = get_transient($key);
        
        if (false === $cached) {
            ob_start();
            call_user_func($callback);
            $cached = ob_get_clean();
            set_transient($key, $cached, $expiration);
        }
        
        echo $cached;
    }

    /**
     * Clear cache by key hoặc pattern
     * 
     * @param string $key Cache key hoặc pattern (vd: 'posts_*')
     * @return void
     */
    public static function clear_cache($key)
    {
        global $wpdb;
        
        if (strpos($key, '*') !== false) {
            // Clear by pattern
            $pattern = str_replace('*', '%', $key);
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$wpdb->options} 
                    WHERE option_name LIKE %s 
                    OR option_name LIKE %s",
                    '_transient_' . $pattern,
                    '_transient_timeout_' . $pattern
                )
            );
        } else {
            // Clear single key
            delete_transient($key);
        }
    }

    /**
     * Clear all transients (toàn bộ cache)
     * 
     * @return int Number of deleted transients
     */
    public static function clear_all_transients()
    {
        global $wpdb;
        
        $count = $wpdb->query(
            "DELETE FROM {$wpdb->options} 
            WHERE option_name LIKE '_transient_%'"
        );
        
        return $count;
    }

    /**
     * Get cache stats
     * 
     * @return array Cache statistics
     */
    public static function get_cache_stats()
    {
        global $wpdb;
        
        $total = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->options} 
            WHERE option_name LIKE '_transient_%' 
            AND option_name NOT LIKE '_transient_timeout_%'"
        );
        
        $expired = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->options} 
            WHERE option_name LIKE '_transient_timeout_%' 
            AND option_value < UNIX_TIMESTAMP()"
        );
        
        $size = $wpdb->get_var(
            "SELECT SUM(LENGTH(option_value)) FROM {$wpdb->options} 
            WHERE option_name LIKE '_transient_%'"
        );
        
        return [
            'total' => (int) $total,
            'expired' => (int) $expired,
            'active' => (int) $total - (int) $expired,
            'size_bytes' => (int) $size,
            'size_mb' => round((int) $size / 1024 / 1024, 2)
        ];
    }

    /**
     * Auto clear cache khi save post
     * 
     * @param int $post_id Post ID
     * @return void
     */
    public static function auto_clear_on_save($post_id)
    {
        // Clear related caches
        $post_type = get_post_type($post_id);
        
        // Clear post type specific cache
        self::clear_cache($post_type . '_*');
        
        // Clear homepage cache nếu là post type quan trọng
        if (in_array($post_type, ['post', 'page', 'service', 'blog'])) {
            self::clear_cache('homepage_*');
            self::clear_cache('recent_posts_*');
        }
    }
}

// Auto hooks
add_action('save_post', ['\App\Settings\MMSTools\CacheHelper', 'auto_clear_on_save']);
add_action('delete_post', ['\App\Settings\MMSTools\CacheHelper', 'auto_clear_on_save']);

