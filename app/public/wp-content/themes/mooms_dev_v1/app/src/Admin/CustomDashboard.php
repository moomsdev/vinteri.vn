<?php
/**
 * Custom Admin Dashboard
 * Enhanced dashboard with custom widgets and statistics
 *
 * @package MoomsDev
 * @since 1.0.0
 */

namespace App\Admin;

class CustomDashboard
{
    public function __construct()
    {
        add_action('wp_dashboard_setup', [$this, 'addCustomDashboardWidgets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueDashboardAssets']);
    }

    /**
     * Add custom dashboard widgets
     */
    public function addCustomDashboardWidgets()
    {
        // Quick Stats Widget
        wp_add_dashboard_widget(
            'mms_quick_stats',
            __('Quick Stats', 'mms'),
            [$this, 'renderQuickStatsWidget']
        );

        // Quick Actions Widget
        wp_add_dashboard_widget(
            'mms_quick_actions',
            __('Quick Actions', 'mms'),
            [$this, 'renderQuickActionsWidget']
        );

        // System Health Widget
        wp_add_dashboard_widget(
            'mms_system_health',
            __('System Health', 'mms'),
            [$this, 'renderSystemHealthWidget']
        );
    }

    /**
     * Enqueue dashboard assets
     */
    public function enqueueDashboardAssets($hook)
    {
        if ($hook !== 'index.php') {
            return;
        }

        // ĐÃ GỘP dashboard.js vào admin bundle, không enqueue riêng nữa
    }

    /**
     * Render Quick Stats Widget
     */
    public function renderQuickStatsWidget()
    {
        $stats = $this->getQuickStats();
        ?>
        <div class="mms-dashboard-stats">
            <div class="stats-grid">
                <!-- Blogs -->
                <div class="stat-item">
                    <div class="stat-number"><?php echo esc_html($stats['blogs']) . ' ' . __('blog posts', 'mms'); ?></div>
                    <div class="stat-list">
                        <div class="item"><?php echo esc_html($stats['blogs_public']) . ' - ' . __('Published', 'mms'); ?></div>
                        <div class="item"><?php echo esc_html($stats['blogs_pending']) . ' - ' . __('Pending', 'mms'); ?></div>
                        <div class="item"><?php echo esc_html($stats['blogs_trash']) . ' - ' . __('Trash', 'mms'); ?></div>
                        <div class="item"><?php echo esc_html($stats['blogs_draft']) . ' - ' . __('Draft', 'mms'); ?></div>
                        <div class="item"><?php echo esc_html($stats['blogs_private']) . ' - ' . __('Private', 'mms'); ?></div>
                        <div class="item"><?php echo esc_html($stats['blogs_scheduled']) . ' - ' . __('Scheduled', 'mms'); ?></div>
                    </div>
                </div>
                <!-- Services -->
                <div class="stat-item">
                    <div class="stat-number"><?php echo esc_html($stats['services']) . ' ' . __('service posts', 'mms'); ?></div>
                    <div class="stat-list">
                        <div class="item"><?php echo esc_html($stats['services_public']) . ' - ' . __('Public', 'mms'); ?></div>
                        <div class="item"><?php echo esc_html($stats['services_pending']) . ' - ' . __('Pending', 'mms'); ?></div>
                        <div class="item"><?php echo esc_html($stats['services_trash']) . ' - ' . __('Trash', 'mms'); ?></div>
                        <div class="item"><?php echo esc_html($stats['services_draft']) . ' - ' . __('Draft', 'mms'); ?></div>
                        <div class="item"><?php echo esc_html($stats['services_private']) . ' - ' . __('Private', 'mms'); ?></div>
                        <div class="item"><?php echo esc_html($stats['services_scheduled']) . ' - ' . __('Scheduled', 'mms'); ?></div>
                    </div>
                </div>
                <!-- Pages -->
                <div class="stat-item">
                    <div class="stat-number"><?php echo esc_html($stats['pages']) . ' ' . __('page', 'mms'); ?></div>
                    <div class="stat-list">
                        <div class="item"><?php echo esc_html($stats['pages_public']) . ' - ' . __('Public', 'mms'); ?></div>
                        <div class="item"><?php echo esc_html($stats['pages_pending']) . ' - ' . __('Pending', 'mms'); ?></div>
                        <div class="item"><?php echo esc_html($stats['pages_trash']) . ' - ' . __('Trash', 'mms'); ?></div>
                        <div class="item"><?php echo esc_html($stats['pages_draft']) . ' - ' . __('Draft', 'mms'); ?></div>
                        <div class="item"><?php echo esc_html($stats['pages_private']) . ' - ' . __('Private', 'mms'); ?></div>
                        <div class="item"><?php echo esc_html($stats['pages_scheduled']) . ' - ' . __('Scheduled', 'mms'); ?></div>
                    </div>
                </div>
                <!-- Media -->
                <div class="stat-item">
                    <div class="stat-number"><?php echo esc_html($stats['media']) . ' ' . __('media', 'mms'); ?></div>
                    <div class="stat-list">
                        <div class="item"><?php echo esc_html($stats['media_images']) . ' ' . __('image', 'mms'); ?></div>
                        <div class="item"><?php echo esc_html($stats['media_videos']) . ' ' . __('video', 'mms'); ?></div>
                        <div class="item"><?php echo esc_html($stats['media_audio']) . ' ' . __('audio', 'mms'); ?></div>
                        <div class="item"><?php echo esc_html($stats['media_documents']) . ' ' . __('document', 'mms'); ?></div>
                    </div>
                </div>
                <!-- Comments -->
                <div class="stat-item">
                    <div class="stat-number"><?php echo esc_html($stats['comments']) . ' ' . __('comment', 'mms') ; ?></div>
                </div>
                <!-- Users -->
                <div class="stat-item">
                    <div class="stat-number"><?php echo esc_html($stats['users']) . ' ' . __('user', 'mms'); ?></div>
                    <div class="stat-list">
                        <div class="item"><?php echo esc_html($stats['users_administrator']) . ' ' . __('administrator', 'mms'); ?></div>
                        <div class="item"><?php echo esc_html($stats['users_editor']) . ' ' . __('editor', 'mms'); ?></div>
                        <div class="item"><?php echo esc_html($stats['users_author']) . ' ' . __('author', 'mms'); ?></div>
                        <div class="item"><?php echo esc_html($stats['users_contributor']) . ' ' . __('contributor', 'mms'); ?></div>
                        <div class="item"><?php echo esc_html($stats['users_subscriber']) . ' ' . __('subscriber', 'mms'); ?></div>
                        <div class="item"><?php echo esc_html($stats['users_others']) . ' ' . __('others', 'mms'); ?></div>
                    </div>
                </div>
            </div>
            <div class="stats-actions">
                <a href="<?php echo admin_url('edit.php'); ?>" class="button button-primary">
                    <?php _e('Blogs', 'mms'); ?>
                </a>
                <a href="<?php echo admin_url('edit.php?post_type=service'); ?>" class="button button-primary">
                    <?php _e('Services', 'mms'); ?>
                </a>
                <a href="<?php echo admin_url('edit.php?post_type=page'); ?>" class="button">
                    <?php _e('Pages', 'mms'); ?>
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Render Recent Activity Widget
     */
    /**
     * Render Quick Actions Widget
     */
    public function renderQuickActionsWidget()
    {
        ?>
        <div class="mms-quick-actions">
            <div class="actions-grid">
                <a href="<?php echo admin_url('post-new.php'); ?>" class="action-item">
                    <span class="dashicons dashicons-edit"></span>
                    <span><?php _e('New Post', 'mms'); ?></span>
                </a>
                <a href="<?php echo admin_url('post-new.php?post_type=page'); ?>" class="action-item">
                    <span class="dashicons dashicons-admin-page"></span>
                    <span><?php _e('New Page', 'mms'); ?></span>
                </a>
                <a href="<?php echo admin_url('upload.php'); ?>" class="action-item">
                    <span class="dashicons dashicons-admin-media"></span>
                    <span><?php _e('Media Library', 'mms'); ?></span>
                </a>
                <a href="<?php echo admin_url('users.php'); ?>" class="action-item">
                    <span class="dashicons dashicons-admin-users"></span>
                    <span><?php _e('Users', 'mms'); ?></span>
                </a>
                <a href="<?php echo admin_url('themes.php'); ?>" class="action-item">
                    <span class="dashicons dashicons-admin-appearance"></span>
                    <span><?php _e('Themes', 'mms'); ?></span>
                </a>
                <a href="<?php echo admin_url('plugins.php'); ?>" class="action-item">
                    <span class="dashicons dashicons-admin-plugins"></span>
                    <span><?php _e('Plugins', 'mms'); ?></span>
                </a>
            </div>
        </div>
        <?php
    }

    /**
     * Render System Health Widget
     */
    public function renderSystemHealthWidget()
    {
        $health = $this->getSystemHealth();
        ?>
        <div class="mms-system-health">
            <div class="health-item">
                <span class="health-label"><?php _e('WordPress Version', 'mms'); ?></span>
                <span class="health-value <?php echo $health['wp_version']['status']; ?>">
                    <?php echo esc_html($health['wp_version']['value']); ?>
                </span>
            </div>
            <div class="health-item">
                <span class="health-label"><?php _e('PHP Version', 'mms'); ?></span>
                <span class="health-value <?php echo $health['php_version']['status']; ?>">
                    <?php echo esc_html($health['php_version']['value']); ?>
                </span>
            </div>
            <div class="health-item">
                <span class="health-label"><?php _e('Memory Limit', 'mms'); ?></span>
                <span class="health-value <?php echo $health['memory_limit']['status']; ?>">
                    <?php echo esc_html($health['memory_limit']['value']); ?>
                </span>
            </div>
            <div class="health-item">
                <span class="health-label"><?php _e('Max Execution Time', 'mms'); ?></span>
                <span class="health-value <?php echo $health['max_execution_time']['status']; ?>">
                    <?php echo esc_html($health['max_execution_time']['value']); ?>
                </span>
            </div>
            <div class="health-item">
                <span class="health-label"><?php _e('Upload Max Size', 'mms'); ?></span>
                <span class="health-value <?php echo $health['upload_max_size']['status']; ?>">
                    <?php echo esc_html($health['upload_max_size']['value']); ?>
                </span>
            </div>
        </div>
        <?php
    }

    /**
     * Get quick statistics
     */
    private function getQuickStats()
    {
        // Safely obtain post counts for custom types
        $blogCounts = wp_count_posts('blog');
        $serviceCounts = wp_count_posts('service');
        $pageCounts = wp_count_posts('page');

        // Helper to get a numeric count or 0 if undefined
        $safe = static function ($obj, $prop) {
            return isset($obj->$prop) ? (int) $obj->$prop : 0;
        };

        // Total = sum of all numeric properties returned by wp_count_posts
        $blogsTotal = array_sum(array_map('intval', (array) $blogCounts));
        $servicesTotal = array_sum(array_map('intval', (array) $serviceCounts));
        // Tính tổng Page theo các trạng thái đang hiển thị để tránh lệch (auto-draft/private không tính)
        $pagesTotal = (
            $safe($pageCounts, 'publish')
            + $safe($pageCounts, 'pending')
            + $safe($pageCounts, 'trash')
            + $safe($pageCounts, 'draft')
            + $safe($pageCounts, 'private')
            + $safe($pageCounts, 'future')
        );

        $userCounts = count_users();
        $roles = isset($userCounts['avail_roles']) ? (array) $userCounts['avail_roles'] : [];

        return [
            // Blogs
            'blogs' => $blogsTotal,
            'blogs_public' => $safe($blogCounts, 'publish'),
            'blogs_pending' => $safe($blogCounts, 'pending'),
            'blogs_trash' => $safe($blogCounts, 'trash'),
            'blogs_draft' => $safe($blogCounts, 'draft'),
            'blogs_private' => $safe($blogCounts, 'private'),
            'blogs_scheduled' => $safe($blogCounts, 'future'),

            // Services
            'services' => $servicesTotal,
            'services_public' => $safe($serviceCounts, 'publish'),
            'services_pending' => $safe($serviceCounts, 'pending'),
            'services_trash' => $safe($serviceCounts, 'trash'),
            'services_draft' => $safe($serviceCounts, 'draft'),
            'services_private' => $safe($serviceCounts, 'private'),
            'services_scheduled' => $safe($serviceCounts, 'future'),

            // Count all attachments (media library items)
            'media' => (function () {
                $attachmentCounts = wp_count_posts('attachment');
                return array_sum(array_map('intval', (array) $attachmentCounts));
            })(),
            // Media by MIME group (sum of object properties)
            'media_images' => (function () { $o = wp_count_attachments('image'); return array_sum(array_map('intval', (array) $o)); })(),
            'media_videos' => (function () { $o = wp_count_attachments('video'); return array_sum(array_map('intval', (array) $o)); })(),
            'media_audio' => (function () { $o = wp_count_attachments('audio'); return array_sum(array_map('intval', (array) $o)); })(),
            // 'application' covers common document mime types (pdf, doc, etc.)
            'media_documents' => (function () { $o = wp_count_attachments('application'); return array_sum(array_map('intval', (array) $o)); })(),

            // Pages
            'pages' => $pagesTotal,
            'pages_public' => $safe($pageCounts, 'publish'),
            'pages_pending' => $safe($pageCounts, 'pending'),
            'pages_trash' => $safe($pageCounts, 'trash'),
            'pages_draft' => $safe($pageCounts, 'draft'),
            'pages_private' => $safe($pageCounts, 'private'),
            'pages_scheduled' => $safe($pageCounts, 'future'),

            // Others
            'comments' => (int) wp_count_comments()->approved,
            'users' => (int) $userCounts['total_users'],
            'users_administrator' => isset($roles['administrator']) ? (int) $roles['administrator'] : 0,
            'users_editor' => isset($roles['editor']) ? (int) $roles['editor'] : 0,
            'users_author' => isset($roles['author']) ? (int) $roles['author'] : 0,
            'users_contributor' => isset($roles['contributor']) ? (int) $roles['contributor'] : 0,
            'users_subscriber' => isset($roles['subscriber']) ? (int) $roles['subscriber'] : 0,
            'users_others' => (function () use ($roles, $userCounts) {
                $known = 0;
                foreach (['administrator','editor','author','contributor','subscriber'] as $r) {
                    $known += isset($roles[$r]) ? (int) $roles[$r] : 0;
                }
                return max(0, (int) $userCounts['total_users'] - $known);
            })(),
        ];
    }

    /**
     * Get system health information
     */
    private function getSystemHealth()
    {
        global $wp_version;

        $health = [];

        // WordPress Version
        $health['wp_version'] = [
            'value' => $wp_version,
            'status' => version_compare($wp_version, '6.0', '>=') ? 'good' : 'warning'
        ];

        // PHP Version
        $php_version = PHP_VERSION;
        $health['php_version'] = [
            'value' => $php_version,
            'status' => version_compare($php_version, '8.0', '>=') ? 'good' : 'warning'
        ];

        // Memory Limit
        $memory_limit = ini_get('memory_limit');
        $memory_bytes = wp_convert_hr_to_bytes($memory_limit);
        $health['memory_limit'] = [
            'value' => $memory_limit,
            'status' => $memory_bytes >= 256 * MB_IN_BYTES ? 'good' : 'warning'
        ];

        // Max Execution Time
        $max_execution_time = ini_get('max_execution_time');
        $health['max_execution_time'] = [
            'value' => $max_execution_time ? $max_execution_time . 's' : 'Unlimited',
            'status' => ($max_execution_time >= 60 || $max_execution_time == 0) ? 'good' : 'warning'
        ];

        // Upload Max Size
        $upload_max_size = ini_get('upload_max_filesize');
        $upload_bytes = wp_convert_hr_to_bytes($upload_max_size);
        $health['upload_max_size'] = [
            'value' => $upload_max_size,
            'status' => $upload_bytes >= 32 * MB_IN_BYTES ? 'good' : 'warning'
        ];

        return $health;
    }
}
