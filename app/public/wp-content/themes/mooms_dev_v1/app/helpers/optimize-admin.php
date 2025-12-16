<?php
// 1. Giảm tần suất Heartbeat API (từ 15s → 60s)
add_filter('heartbeat_settings', function ($settings) {
    // Dashboard: 60s (thay vì 15s)
    $settings['interval'] = 60;
    
    // Khi edit post: 30s (thay vì 15s)
    if (isset($_GET['post'])) {
        $settings['interval'] = 30;
    }
    
    return $settings;
});

// 2. Tắt Heartbeat hoàn toàn ngoại trừ post editor
add_action('init', function () {
    global $pagenow;
    
    // Danh sách trang KHÔNG CẦN heartbeat
    $disabled_pages = [
        'index.php',        // Dashboard
        'plugins.php',      // Plugins
        'themes.php',       // Themes
        'users.php',        // Users
        'tools.php',        // Tools
        'options-general.php', // Settings
    ];
    
    if (in_array($pagenow, $disabled_pages)) {
        wp_deregister_script('heartbeat');
    }
}, 1);

// 3. Giảm auto-save (từ 60s → 120s)
if (!defined('AUTOSAVE_INTERVAL')) {
    define('AUTOSAVE_INTERVAL', 120); // 2 phút
}

// 4. Tắt emoji scripts (không cần trong admin)
remove_action('admin_print_scripts', 'print_emoji_detection_script');
remove_action('admin_print_styles', 'print_emoji_styles');

// 5. Giảm post revisions (từ unlimited → 5)
if (!defined('WP_POST_REVISIONS')) {
    define('WP_POST_REVISIONS', 5);
}

// 6. Tắt admin notices từ plugins (giảm requests)
// TẠM TẮT để tránh ẩn thông báo quan trọng và xung đột UI
// add_action('admin_enqueue_scripts', function () {
//     remove_all_actions('admin_notices');
//     remove_all_actions('network_admin_notices');
//     remove_all_actions('all_admin_notices');
//     remove_all_actions('user_admin_notices');
// }, 0);

// 7. Defer non-critical admin scripts
add_filter('script_loader_tag', function ($tag, $handle) {
    // Không can thiệp script trong admin để tránh lỗi jQuery UI (a.widget is not a function)
    if (is_admin()) {
        return $tag;
    }
    // Danh sách scripts có thể defer
    $defer_scripts = [
        'jquery-ui-core',
        'jquery-ui-widget',
        'jquery-ui-mouse',
        'jquery-ui-sortable',
        'jquery-repeater',
    ];
    
    if (in_array($handle, $defer_scripts)) {
        return str_replace(' src', ' defer src', $tag);
    }
    
    return $tag;
}, 10, 2);


// 9. Tối ưu admin menu queries
add_action('admin_menu', function () {
    // Remove unnecessary menus nhanh hơn
    global $menu, $submenu;
    
    // Cache menu structure
    $cached_menu = wp_cache_get('custom_admin_menu', 'mms');
    if (false === $cached_menu) {
        wp_cache_set('custom_admin_menu', $menu, 'mms', 3600);
    }
}, 999);

// 10. Log slow admin requests (để debug)
add_action('shutdown', function () {
    $elapsed = timer_stop();
    
    // Nếu request > 1s, log ra
    if ($elapsed > 1 && is_admin()) {
        global $pagenow;
        error_log(sprintf(
            'Slow admin page: %s - %.2fs - Memory: %s',
            $pagenow,
            $elapsed,
            size_format(memory_get_peak_usage(true))
        ));
    }
});
