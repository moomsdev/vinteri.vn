<?php

namespace App\Settings\MMSTools;

/**
 * Database Cleaner - Auto cleanup và optimize database
 * Dọn dẹp revisions, orphaned data, expired transients
 */
class DatabaseCleaner
{
    public function __construct()
    {
        // Schedule weekly cleanup
        if (!wp_next_scheduled('mms_database_cleanup')) {
            wp_schedule_event(time(), 'weekly', 'mms_database_cleanup');
        }
        
        add_action('mms_database_cleanup', [$this, 'run_cleanup']);
        
        // Admin hooks
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_post_mms_cleanup_now', [$this, 'manual_cleanup']);
        add_action('admin_notices', [$this, 'show_admin_notices']);
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu()
    {
        add_management_page(
            __('Database Cleanup', 'mms'),
            __('DB Cleanup', 'mms'),
            'manage_options',
            'mms-db-cleanup',
            [$this, 'render_admin_page']
        );
    }

    /**
     * Show admin notices
     */
    public function show_admin_notices()
    {
        if (isset($_GET['page']) && $_GET['page'] === 'mms-db-cleanup' && isset($_GET['cleaned'])) {
            $count = intval($_GET['cleaned']);
            ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <strong><?php _e('Cleanup hoàn tất!', 'mms'); ?></strong>
                    <?php echo sprintf(__('Đã xóa %s items và tối ưu database.', 'mms'), number_format($count)); ?>
                </p>
            </div>
            <?php
        }
    }

    /**
     * Render admin page
     */
    public function render_admin_page()
    {
        $stats = $this->get_cleanup_stats();
        ?>
        <div class="wrap">
            <h1><?php _e('Database Cleanup', 'mms'); ?></h1>
            
            <div class="card">
                <h2><?php _e('Cleanup Statistics', 'mms'); ?></h2>
                <table class="widefat">
                    <tr>
                        <td><?php _e('Post Revisions', 'mms'); ?></td>
                        <td><strong><?php echo number_format($stats['revisions']); ?></strong></td>
                    </tr>
                    <tr>
                        <td><?php _e('Auto Drafts', 'mms'); ?></td>
                        <td><strong><?php echo number_format($stats['auto_drafts']); ?></strong></td>
                    </tr>
                    <tr>
                        <td><?php _e('Trashed Posts', 'mms'); ?></td>
                        <td><strong><?php echo number_format($stats['trash']); ?></strong></td>
                    </tr>
                    <tr>
                        <td><?php _e('Orphaned Postmeta', 'mms'); ?></td>
                        <td><strong><?php echo number_format($stats['orphaned_meta']); ?></strong></td>
                    </tr>
                    <tr>
                        <td><?php _e('Expired Transients', 'mms'); ?></td>
                        <td><strong><?php echo number_format($stats['expired_transients']); ?></strong></td>
                    </tr>
                    <tr>
                        <td><?php _e('Database Size', 'mms'); ?></td>
                        <td><strong><?php echo $stats['db_size']; ?> MB</strong></td>
                    </tr>
                </table>
            </div>

            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="mms_cleanup_now">
                <?php wp_nonce_field('mms_cleanup_now', 'mms_cleanup_nonce'); ?>
                
                <p>
                    <button type="submit" class="button button-primary button-large">
                        <?php _e('Run Cleanup Now', 'mms'); ?>
                    </button>
                </p>
                
                <p class="description">
                    <?php _e('Auto cleanup chạy hàng tuần. Bạn có thể chạy manual bất cứ lúc nào.', 'mms'); ?>
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Manual cleanup từ admin
     */
    public function manual_cleanup()
    {
        check_admin_referer('mms_cleanup_now', 'mms_cleanup_nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'mms'));
        }
        
        $result = $this->run_cleanup();
        
        wp_redirect(add_query_arg([
            'page' => 'mms-db-cleanup',
            'cleaned' => $result['total']
        ], admin_url('tools.php')));
        exit;
    }

    /**
     * Get cleanup statistics
     */
    public function get_cleanup_stats()
    {
        global $wpdb;
        
        return [
            'revisions' => $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'revision'"
            ),
            'auto_drafts' => $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'auto-draft'"
            ),
            'trash' => $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'trash'"
            ),
            'orphaned_meta' => $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->postmeta} pm 
                LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id 
                WHERE p.ID IS NULL"
            ),
            'expired_transients' => $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->options} 
                WHERE option_name LIKE '_transient_timeout_%' 
                AND option_value < UNIX_TIMESTAMP()"
            ),
            'db_size' => $this->get_database_size()
        ];
    }

    /**
     * Run cleanup
     */
    public function run_cleanup()
    {
        global $wpdb;
        
        $results = [];
        
        // 1. Delete old revisions (> 30 days)
        $results['revisions'] = $wpdb->query(
            "DELETE FROM {$wpdb->posts} 
            WHERE post_type = 'revision' 
            AND post_modified < DATE_SUB(NOW(), INTERVAL 30 DAY)"
        );
        
        // 2. Delete auto-drafts (> 7 days)
        $results['auto_drafts'] = $wpdb->query(
            "DELETE FROM {$wpdb->posts} 
            WHERE post_status = 'auto-draft' 
            AND post_modified < DATE_SUB(NOW(), INTERVAL 7 DAY)"
        );
        
        // 3. Delete trashed posts (> 30 days)
        $results['trash'] = $wpdb->query(
            "DELETE FROM {$wpdb->posts} 
            WHERE post_status = 'trash' 
            AND post_modified < DATE_SUB(NOW(), INTERVAL 30 DAY)"
        );
        
        // 4. Delete orphaned postmeta
        $results['orphaned_meta'] = $wpdb->query(
            "DELETE pm FROM {$wpdb->postmeta} pm 
            LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id 
            WHERE p.ID IS NULL"
        );
        
        // 5. Delete orphaned term relationships
        $results['orphaned_terms'] = $wpdb->query(
            "DELETE tr FROM {$wpdb->term_relationships} tr
            LEFT JOIN {$wpdb->posts} p ON p.ID = tr.object_id
            WHERE p.ID IS NULL"
        );
        
        // 6. Delete expired transients
        $results['expired_transients'] = $wpdb->query(
            "DELETE FROM {$wpdb->options} 
            WHERE option_name LIKE '_transient_timeout_%' 
            AND option_value < UNIX_TIMESTAMP()"
        );
        
        // 6b. Delete transient keys không có timeout tương ứng (tránh subselect trực tiếp cùng bảng)
        $wpdb->query(
            "DELETE o FROM {$wpdb->options} o
            LEFT JOIN (
                SELECT REPLACE(option_name, '_timeout', '') AS t_key
                FROM {$wpdb->options}
                WHERE option_name LIKE '_transient_timeout_%'
            ) t ON o.option_name = t.t_key
            WHERE o.option_name LIKE '_transient_%'
            AND o.option_name NOT LIKE '_transient_timeout_%'
            AND t.t_key IS NULL"
        );
        
        // 7. Optimize tables
        $tables = [
            $wpdb->posts,
            $wpdb->postmeta,
            $wpdb->options,
            $wpdb->terms,
            $wpdb->term_taxonomy,
            $wpdb->term_relationships
        ];
        
        foreach ($tables as $table) {
            $wpdb->query("OPTIMIZE TABLE {$table}");
        }
        
        $results['total'] = array_sum($results);
        
        // Log kết quả
        error_log('Database cleanup completed: ' . json_encode($results));
        
        return $results;
    }

    /**
     * Get database size
     */
    private function get_database_size()
    {
        global $wpdb;
        
        $size = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(data_length + index_length) / 1024 / 1024 
                FROM information_schema.TABLES 
                WHERE table_schema = %s",
                DB_NAME
            )
        );
        
        return round($size, 2);
    }
}

