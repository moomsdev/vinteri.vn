<?php

namespace App\Settings\MMSTools;

/**
 * Query Optimizer - Tối ưu WP_Query và prevent N+1 queries
 */
class QueryOptimizer
{
    /**
     * Optimize WP_Query args
     * Thêm các optimization flags vào query args
     * 
     * @param array $args Original query args
     * @param array $options Optimization options
     * @return array Optimized args
     */
    public static function optimize_query_args($args, $options = [])
    {
        $defaults = [
            'no_found_rows' => true,          // Bỏ COUNT query nếu không cần pagination
            'update_post_meta_cache' => true, // Có cần meta không?
            'update_post_term_cache' => true, // Có cần terms không?
            'fields' => 'all'                  // 'ids' nếu chỉ cần IDs
        ];
        
        $options = wp_parse_args($options, $defaults);
        
        // Merge vào args
        $args = array_merge($args, $options);
        
        return $args;
    }

    /**
     * Get optimized posts query
     * Wrapper cho WP_Query với optimization mặc định
     * 
     * @param array $args Query arguments
     * @return \WP_Query
     */
    public static function get_optimized_query($args)
    {
        // Tự động optimize nếu không cần pagination
        if (!isset($args['paged']) && !isset($args['offset'])) {
            $args['no_found_rows'] = true;
        }
        
        return new \WP_Query($args);
    }

    /**
     * Prime term cache - Tránh N+1 queries khi lấy terms
     * 
     * @param array $post_ids Array of post IDs
     * @param string|array $taxonomies Taxonomy names
     * @return void
     */
    public static function prime_term_cache($post_ids, $taxonomies = [])
    {
        if (empty($post_ids)) {
            return;
        }
        
        if (empty($taxonomies)) {
            $taxonomies = get_object_taxonomies('post');
        }
        
        update_object_term_cache($post_ids, $taxonomies);
    }

    /**
     * Prime post meta cache - Tránh N+1 queries khi lấy meta
     * 
     * @param array $post_ids Array of post IDs
     * @return void
     */
    public static function prime_post_meta_cache($post_ids)
    {
        if (empty($post_ids)) {
            return;
        }
        
        update_meta_cache('post', $post_ids);
    }

    /**
     * Get posts với auto cache priming
     * 
     * @param array $args Query args
     * @param bool $prime_meta Prime meta cache?
     * @param bool $prime_terms Prime terms cache?
     * @return array Posts
     */
    public static function get_posts_optimized($args, $prime_meta = true, $prime_terms = true)
    {
        $query = self::get_optimized_query($args);
        $posts = $query->posts;
        
        if (empty($posts)) {
            return [];
        }
        
        $post_ids = wp_list_pluck($posts, 'ID');
        
        if ($prime_meta) {
            self::prime_post_meta_cache($post_ids);
        }
        
        if ($prime_terms) {
            self::prime_term_cache($post_ids);
        }
        
        return $posts;
    }

    /**
     * Batch get post meta - Lấy meta cho nhiều posts cùng lúc
     * 
     * @param array $post_ids Post IDs
     * @param string $meta_key Meta key
     * @return array [post_id => meta_value]
     */
    public static function get_batch_post_meta($post_ids, $meta_key)
    {
        global $wpdb;
        
        if (empty($post_ids)) {
            return [];
        }
        
        $post_ids_string = implode(',', array_map('intval', $post_ids));
        
        $results = $wpdb->get_results(
            "SELECT post_id, meta_value 
            FROM {$wpdb->postmeta} 
            WHERE post_id IN ({$post_ids_string}) 
            AND meta_key = '{$meta_key}'",
            OBJECT_K
        );
        
        $output = [];
        foreach ($results as $post_id => $row) {
            $output[$post_id] = maybe_unserialize($row->meta_value);
        }
        
        return $output;
    }

    /**
     * Get related posts optimized
     * 
     * @param int $post_id Post ID
     * @param int $limit Number of posts
     * @return array Related posts
     */
    public static function get_related_posts($post_id, $limit = 5)
    {
        $cache_key = "related_posts_{$post_id}_{$limit}";
        
        return CacheHelper::get_cached_data($cache_key, function() use ($post_id, $limit) {
            $post = get_post($post_id);
            $terms = get_the_terms($post_id, 'category');
            
            if (!$terms || is_wp_error($terms)) {
                return [];
            }
            
            $term_ids = wp_list_pluck($terms, 'term_id');
            
            $args = [
                'post_type' => $post->post_type,
                'posts_per_page' => $limit,
                'post__not_in' => [$post_id],
                'tax_query' => [
                    [
                        'taxonomy' => 'category',
                        'field' => 'term_id',
                        'terms' => $term_ids
                    ]
                ],
                'no_found_rows' => true,
                'update_post_term_cache' => false
            ];
            
            return get_posts($args);
        }, 3600);
    }

    /**
     * Monitor query performance
     * 
     * @param string $query_name Query identifier
     * @param callable $callback Query function
     * @return mixed Query result
     */
    public static function monitor_query($query_name, $callback)
    {
        $start = microtime(true);
        
        $result = call_user_func($callback);
        
        $time = microtime(true) - $start;
        
        if ($time > 0.1) { // Log nếu > 100ms
            error_log(sprintf(
                'Slow query detected: %s took %.3f seconds',
                $query_name,
                $time
            ));
        }
        
        return $result;
    }

    /**
     * Get query performance report
     * 
     * @return array Performance stats
     */
    public static function get_performance_report()
    {
        global $wpdb;
        
        return [
            'total_queries' => $wpdb->num_queries,
            'query_time' => timer_stop(0, 8),
            'cache_stats' => CacheHelper::get_cache_stats()
        ];
    }
}

