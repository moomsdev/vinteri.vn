<?php

namespace App\Settings\MMSTools;

class Security
{
    protected $currentUser;

	protected $superUsers = SUPER_USER;

	protected $errorMessage = '';

    public function __construct()
    {
        $this->currentUser = wp_get_current_user();

        // Enhance website security
        if (get_option('_disable_rest_api') === 'yes') {
            $this->disableRestApi();
        }

        if (get_option('_disable_xml_rpc') === 'yes') {
            $this->disableXmlRpc();
        }

        if (get_option('_disable_wp_embed') === 'yes') {
            $this->disableWpEmbed();
        }

        if (get_option('_disable_wp_cron') === 'yes') {
            $this->disableWpCron();
        }

        if (get_option('_disable_x_pingback') === 'yes') {
            $this->disableXPingback();
        }

        if (get_option('_enable_remove_wordpress_bloat') === 'yes') {
            $this->removeWordpressBloat();
        }

        if (get_option('_enable_optimize_database_queries') === 'yes') {
            $this->optimizeDatabaseQueries();   
        }

        if (get_option('_enable_optimize_sql_queries') === 'yes') {
            // Hook vào filter 'query' để nhận tham số $query đúng cách thay vì gọi trực tiếp
            add_filter('query', [$this, 'optimizeSqlQueries']);
        }

        if (get_option('_enable_optimize_memory_usage') === 'yes') {
            $this->optimizeMemoryUsage();
        }

        if (get_option('_enable_cleanup_memory') === 'yes') {
            $this->cleanupMemory();
        }

        if (get_option('_enable_set_cache_headers') === 'yes') {
            $this->setCacheHeaders();
        }

        if (get_option('_enable_compression') === 'yes') {
            $this->enableCompression();
        }

        if (get_option('_enable_performance_monitoring') === 'yes') {
            $this->addPerformanceMonitoring();
        }
    }

    public function disableRestApi()
    {
        add_filter( 'rest_authentication_errors', function( $result ) {
            // Nếu đã có lỗi hoặc đã authenticated, giữ nguyên
            if ( true === $result || is_wp_error( $result ) ) {
                return $result;
            }

            // QUAN TRỌNG: Cho phép admin và logged-in users
            if ( is_user_logged_in() ) {
                return $result;
            }

            // CHỈ block REST API cho anonymous users
            // NHƯNG vẫn cho phép một số endpoints cần thiết
            global $wp;
            $current_route = $wp->query_vars['rest_route'] ?? '';
            
            // Whitelist: cho phép các endpoints này
            $allowed_routes = [
                '/wp/v2/types',
                '/wp/v2/statuses', 
                '/wp/v2/taxonomies',
                '/wp/v2/users/me',
                '/oembed/',
            ];
            
            foreach ($allowed_routes as $allowed) {
                if (strpos($current_route, $allowed) !== false) {
                    return $result;
                }
            }

            // Block các routes còn lại cho anonymous
            return new \WP_Error( 'rest_not_logged_in',  __('You are not logged in', 'mms'), array( 'status' => 401 ) );
        });
    }

    public function disableXmlRpc()
    {
        add_filter( 'wp_xmlrpc_server_class', '__return_false' );
        add_filter('xmlrpc_enabled', '__return_false');
        add_filter('pre_update_option_enable_xmlrpc', '__return_false');
        add_filter('pre_option_enable_xmlrpc', '__return_zero');
    }

    public function disableWpEmbed()
    {
        add_action('init', function() {
            wp_deregister_script('wp-embed');
            remove_action('wp_head', 'wp_oembed_add_discovery_links');
            remove_action('wp_head', 'wp_oembed_add_host_js');
        });
    }

    /**
     * Tắt WP-Cron (mang tính biểu trưng nếu define muộn). Khuyến nghị đặt trong wp-config.php.
     */
    public function disableWpCron()
    {
        if (!defined('DISABLE_WP_CRON')) {
            define('DISABLE_WP_CRON', true);
        }
    }

    public function disableXPingback()
    {
        add_filter('wp_headers', function($headers) {
            if (isset($headers['X-Pingback'])) {
                unset($headers['X-Pingback']);
            }
            return $headers;
        });
    }

    /**
     * Loại bỏ các thành phần không cần thiết của WordPress để tăng bảo mật và hiệu suất
     */
    public function removeWordpressBloat()
    {
        remove_action('wp_head', 'rsd_link');
        remove_action('wp_head', 'wlwmanifest_link');
        remove_action('wp_head', 'wp_generator');
        remove_action('wp_head', 'feed_links', 2);
        remove_action('wp_head', 'feed_links_extra', 3);
        remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10);
        remove_action('wp_head', 'wp_shortlink_wp_head', 10);
        remove_action('wp_head', 'start_post_rel_link');
        remove_action('wp_head', 'index_rel_link');
        remove_action('wp_head', 'parent_post_rel_link');
        // Tối ưu heartbeat
        add_filter('heartbeat_settings', function ($settings) {
            $settings['interval'] = 120; // 2 phút thay vì 15 giây
            return $settings;
        });
        // Ẩn lỗi đăng nhập
        add_filter('login_errors', '__return_null');
    }

    /**
     * Giới hạn số lượng post revision và tăng autosave interval
     */
    public function optimizeDatabaseQueries()
    {
        if (!defined('WP_POST_REVISIONS')) {
            define('WP_POST_REVISIONS', 3);
        }
        if (!defined('AUTOSAVE_INTERVAL')) {
            define('AUTOSAVE_INTERVAL', 300); // 5 phút
        }
        if (function_exists('wp_cache_set')) {
            wp_cache_set('performance_optimized', true, 'theme', 3600);
        }
    }

    /**
     * Log các truy vấn SQL chậm để phát hiện truy vấn bất thường
     */
    public function optimizeSqlQueries($query)
    {
        if (strpos($query, 'SELECT') === 0) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                $start_time = microtime(true);
                register_shutdown_function(function () use ($start_time, $query) {
                    $execution_time = microtime(true) - $start_time;
                    if ($execution_time > 0.5) {
                        error_log("Slow query detected: {$execution_time}s - {$query}");
                    }
                });
            }
        }
        return $query;
    }

    /**
     * Tăng memory limit và bật garbage collection
     */
    public function optimizeMemoryUsage()
    {
        if (function_exists('ini_get') && ini_get('memory_limit') < 256) {
            ini_set('memory_limit', '256M');
        }
        if (function_exists('gc_enable')) {
            gc_enable();
        }
    }

    /**
     * Dọn dẹp bộ nhớ cuối trang
     */
    public function cleanupMemory()
    {
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
    }

    /**
     * Đặt cache header bảo vệ trang admin và user login
     */
    public function setCacheHeaders()
    {
        if (!is_admin() && !is_user_logged_in()) {
            if (preg_match('/\.(css|js|png|jpg|jpeg|gif|webp|svg|woff|woff2|ttf|eot|ico)$/', $_SERVER['REQUEST_URI'])) {
                header('Cache-Control: public, max-age=31536000, immutable');
                header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
                header('Pragma: public');
            } else {
                header('Cache-Control: public, max-age=3600, must-revalidate');
                header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');
                header('Vary: Accept-Encoding');
            }
        }
    }

    /**
     * Bật gzip để bảo vệ dữ liệu truyền tải
     */
    public function enableCompression()
    {
        if (!is_admin()) {
            if (function_exists('gzencode') && !ob_get_contents()) {
                ob_start('ob_gzhandler');
            }
        }
    }

    /**
     * Giám sát hiệu suất, phát hiện bất thường
     */
    public function addPerformanceMonitoring()
    {
        // QUAN TRỌNG: Phải dùng wp_footer hook, KHÔNG được output trực tiếp!
        add_action('wp_footer', function () {
            if (!is_admin()) {
                ?>
                <script>
                    if ('PerformanceObserver' in window) {
                        new PerformanceObserver((entryList) => {
                            for (const entry of entryList.getEntries()) {
                                console.log('LCP:', entry.startTime);
                            }
                        }).observe({ type: 'largest-contentful-paint', buffered: true });
                        new PerformanceObserver((entryList) => {
                            for (const entry of entryList.getEntries()) {
                                if (!entry.hadRecentInput) {
                                    console.log('CLS:', entry.value);
                                }
                            }
                        }).observe({ type: 'layout-shift', buffered: true });
                        new PerformanceObserver((entryList) => {
                            for (const entry of entryList.getEntries()) {
                                console.log('FID:', entry.processingStart - entry.startTime);
                            }
                        }).observe({ type: 'first-input', buffered: true });
                    }
                    if ('performance' in window && 'mark' in performance) {
                        performance.mark('theme-loaded');
                    }
                </script>
                <?php
            }
        }, 999);
    }
}