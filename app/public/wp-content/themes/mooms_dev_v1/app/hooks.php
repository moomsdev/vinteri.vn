<?php
/**
 * Declare all your actions and filters here.
 *
 * @package WPEmergeTheme
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * ------------------------------------------------------------------------
 * WordPress
 * ------------------------------------------------------------------------
 */

/**
 * Assets
 */
add_action('wp_enqueue_scripts', 'app_action_theme_enqueue_assets');
add_action('admin_enqueue_scripts', 'app_action_admin_enqueue_assets');
add_action('login_enqueue_scripts', 'app_action_login_enqueue_assets');
add_action('enqueue_block_editor_assets', 'app_action_editor_enqueue_assets');
add_action('wp_head', 'app_action_add_favicon', 5);
add_action('login_head', 'app_action_add_favicon', 5);
add_action('admin_head', 'app_action_add_favicon', 5);
add_filter('upload_dir', 'app_filter_fix_upload_dir_url_schema');

/**
 * Content
 */
add_filter('excerpt_more', 'app_filter_excerpt_more');
add_filter('excerpt_length', 'app_filter_excerpt_length', 999);
add_filter('the_content', 'app_filter_fix_shortcode_empty_paragraphs');

// Attach all suitable hooks from `the_content` on `app_content`.
add_filter('app_content', 'do_shortcode', 9);
add_filter('app_content', 'app_filter_fix_shortcode_empty_paragraphs', 10);
add_filter('app_content', 'wptexturize', 10);
add_filter('app_content', 'wpautop', 10);
add_filter('app_content', 'shortcode_unautop', 10);
add_filter('app_content', 'prepend_attachment', 10);
add_filter('app_content', 'wp_make_content_images_responsive', 10);
add_filter('app_content', 'convert_smilies', 20);

/**
 * Login
 */
add_filter('login_headerurl', 'app_filter_login_headerurl');
if (version_compare(get_bloginfo('version'), '5.2', '<')) {
    add_filter('login_headertext', 'app_filter_login_headertext');
}
add_filter('login_headertext', 'app_filter_login_headertext');

/**
 * Load Gutenberg blocks initialization
 * Theo cách của timeline-block: mỗi block có init riêng
 */
$gutenberg_blocks_init = get_template_directory() . '/setup/blocks-gutenberg/init.php';
if (file_exists($gutenberg_blocks_init)) {
    require_once $gutenberg_blocks_init;
}

// Thêm category "Mooms Blocks" cho khung chèn block
add_filter('block_categories_all', function($categories){
    $custom = [
        [
            'slug' => 'mms-blocks',
            'title' => __('Mooms Blocks', 'mms'),
            'icon' => 'layout'
        ]
    ];
    // Đưa category của chúng ta lên đầu
    return array_merge($custom, $categories);
}, 10, 1);

/**
 * Widgets: đảm bảo có ít nhất 1 sidebar được đăng ký
 * Nếu theme chưa đăng ký sidebar nào, menu Widgets sẽ không hiển thị
 */
add_action('widgets_init', function () {
    global $wp_registered_sidebars;
    if (empty($wp_registered_sidebars) || !is_array($wp_registered_sidebars)) {
        register_sidebar([
            'name'          => __('Primary Sidebar', 'mms'),
            'id'            => 'primary-sidebar',
            'description'   => __('Default sidebar for widgets', 'mms'),
            'before_widget' => '<section id="%1$s" class="widget %2$s">',
            'after_widget'  => '</section>',
            'before_title'  => '<h2 class="widget-title">',
            'after_title'   => '</h2>',
        ]);
    }
});

/**
 * Pages list table: thêm cột Thumbnail giống CPT
 */
add_filter('manage_page_posts_columns', function ($cols) {
    if (is_array($cols)) {
        $cols = insertArrayAtPosition($cols, ['featured_image' => 'Image'], 1);
    }
    return $cols;
}, 9999);

add_action('manage_page_posts_custom_column', function ($column, $postId) {
    if ($column !== 'featured_image') {
        return;
    }
    $thumbnailUrl = get_the_post_thumbnail_url($postId);
    echo "<a href='javascript:' data-trigger-change-thumbnail-id data-post-id='{$postId}'>";
    if ($thumbnailUrl) {
        echo "<img src='" . esc_url($thumbnailUrl) . "' alt='' />";
    } else {
        echo "<div class='no-image-text'>Choose Image</div>";
    }
    echo "</a>";
}, 10, 2);

/**
 * ------------------------------------------------------------------------
 * External Libraries and Plugins.
 * ------------------------------------------------------------------------
 */

/**
 * Carbon Fields
 */
// add_action( 'after_setup_theme', 'app_bootstrap_carbon_fields', 100 );
add_action('carbon_fields_register_fields', 'app_bootstrap_carbon_fields_register_fields');
add_filter('carbon_fields_map_field_api_key', 'app_filter_carbon_fields_google_maps_api_key');
