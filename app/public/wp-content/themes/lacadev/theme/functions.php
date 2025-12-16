<?php
use WPEmerge\Facades\WPEmerge;
use WPEmergeTheme\Facades\Theme;

if (!defined('ABSPATH')) {
    exit;
}

// =============================================================================
// SECURITY & PERMISSIONS
// =============================================================================
define('SUPER_USER', ['lacadev']);

// =============================================================================
// THEME INFORMATION
// =============================================================================
define('AUTHOR', [
    'name' => 'La Cà Dev',
    'email' => 'mooms.dev@gmail.com',
    'phone_number' => '0989.64.67.66',
    'website' => 'https://lacadev.com/',
    'date_started' => get_option('_theme_info_date_started'),
    'date_published' => get_option('_theme_info_date_publish'),
]);

// =============================================================================
// DIRECTORY CONSTANTS
// =============================================================================

// Directory Names
define('APP_APP_DIR_NAME', 'app');
define('APP_APP_HELPERS_DIR_NAME', 'helpers');
define('APP_APP_ROUTES_DIR_NAME', 'routes');
define('APP_APP_SETUP_DIR_NAME', 'setup');
define('APP_DIST_DIR_NAME', 'dist');
define('APP_RESOURCES_DIR_NAME', 'resources');
define('APP_THEME_DIR_NAME', 'theme');
define('APP_VENDOR_DIR_NAME', 'vendor');

// Theme Component Names
define('APP_THEME_USER_NAME', 'users');
define('APP_THEME_ECOMMERCE_NAME', 'users');
define('APP_THEME_TAXONOMY_NAME', 'taxonomies');
define('APP_THEME_WIDGET_NAME', 'widgets');
define('APP_THEME_BLOCK_NAME', 'blocks');
define('APP_THEME_WALKER_NAME', 'walkers');

// Directory Paths
define('APP_DIR', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('APP_APP_DIR', APP_DIR . APP_APP_DIR_NAME . DIRECTORY_SEPARATOR);
define('APP_APP_HELPERS_DIR', APP_APP_DIR . APP_APP_HELPERS_DIR_NAME . DIRECTORY_SEPARATOR);
define('APP_APP_ROUTES_DIR', APP_APP_DIR . APP_APP_ROUTES_DIR_NAME . DIRECTORY_SEPARATOR);
define('APP_RESOURCES_DIR', APP_DIR . APP_RESOURCES_DIR_NAME . DIRECTORY_SEPARATOR);
define('APP_THEME_DIR', APP_DIR . APP_THEME_DIR_NAME . DIRECTORY_SEPARATOR);
define('APP_VENDOR_DIR', APP_DIR . APP_VENDOR_DIR_NAME . DIRECTORY_SEPARATOR);
define('APP_DIST_DIR', APP_DIR . APP_DIST_DIR_NAME . DIRECTORY_SEPARATOR);

// Setup Directories
define('APP_APP_SETUP_DIR', APP_THEME_DIR . APP_APP_SETUP_DIR_NAME . DIRECTORY_SEPARATOR);
define('APP_APP_SETUP_ECOMMERCE_DIR', APP_THEME_DIR . APP_APP_SETUP_DIR_NAME . DIRECTORY_SEPARATOR . APP_THEME_ECOMMERCE_NAME . DIRECTORY_SEPARATOR);
define('APP_APP_SETUP_USER_DIR', APP_THEME_DIR . APP_APP_SETUP_DIR_NAME . DIRECTORY_SEPARATOR . APP_THEME_USER_NAME . DIRECTORY_SEPARATOR);
define('APP_APP_SETUP_TAXONOMY_DIR', APP_THEME_DIR . APP_APP_SETUP_DIR_NAME . DIRECTORY_SEPARATOR . APP_THEME_TAXONOMY_NAME . DIRECTORY_SEPARATOR);
define('APP_APP_SETUP_WIDGET_DIR', APP_THEME_DIR . APP_APP_SETUP_DIR_NAME . DIRECTORY_SEPARATOR . APP_THEME_WIDGET_NAME . DIRECTORY_SEPARATOR);
define('APP_APP_SETUP_BLOCK_DIR', APP_THEME_DIR . APP_APP_SETUP_DIR_NAME . DIRECTORY_SEPARATOR . APP_THEME_BLOCK_NAME . DIRECTORY_SEPARATOR);
define('APP_APP_SETUP_WALKER_DIR', APP_THEME_DIR . APP_APP_SETUP_DIR_NAME . DIRECTORY_SEPARATOR . APP_THEME_WALKER_NAME . DIRECTORY_SEPARATOR);

// =============================================================================
// DEPENDENCIES & AUTOLOADING
// =============================================================================

// Load composer dependencies
if (file_exists(APP_VENDOR_DIR . 'autoload.php')) {
    require_once APP_VENDOR_DIR . 'autoload.php';
    \Carbon_Fields\Carbon_Fields::boot();
}

// Enable Theme shortcut
WPEmerge::alias('Theme', \WPEmergeTheme\Facades\Theme::class);

// Load helpers
require_once APP_APP_DIR . 'helpers.php';

// Load responsive image helpers (NEW - for automatic srcset/sizes)
require_once APP_APP_DIR . 'helpers/responsive-images.php';

// Bootstrap Theme
Theme::bootstrap(require APP_APP_DIR . 'config.php');

// Register hooks
require_once APP_APP_DIR . 'hooks.php';

// =============================================================================
// THEME SETUP
// =============================================================================
add_action('after_setup_theme', function () {
    // Load textdomain
    load_theme_textdomain('laca', APP_DIR . 'languages');

    // Load theme components
    require_once APP_APP_SETUP_DIR . 'theme-support.php';
    require_once APP_APP_SETUP_DIR . 'menus.php';

    // Load security & SEO (Phase 1 improvements)
    require_once APP_APP_SETUP_DIR . 'security.php';
    require_once APP_APP_SETUP_DIR . 'seo.php';

    // Load image optimization (Phase 2 improvements)
    require_once APP_APP_SETUP_DIR . 'image-optimization.php';

    // Load advanced optimization modules
    require_once APP_APP_SETUP_DIR . 'assets.php';
    require_once APP_APP_SETUP_DIR . 'performance.php';

    // Load Custom Post Order
    require_once APP_APP_DIR . 'src/Settings/PostOrder.php';
    new \App\Settings\PostOrder();

    // Load Gutenberg blocks (Carbon Fields)
    // $blocks_dir = APP_APP_SETUP_DIR . '/blocks';
    // $block_files = glob($blocks_dir . '/*.php');
    // foreach ($block_files as $block_file) {
    //     require_once $block_file;
    // }

    // Load ReactJS Gutenberg blocks
    require_once APP_APP_SETUP_DIR . 'gutenberg-blocks.php';

});

// =============================================================================
// AUTOLOAD COMPONENTS
// =============================================================================
$folders = [
    APP_APP_SETUP_ECOMMERCE_DIR,
    APP_APP_SETUP_TAXONOMY_DIR,
    APP_APP_SETUP_WALKER_DIR,
];

foreach ($folders as $folder) {
    $filesPath = scandir($folder);
    if ($filesPath !== false) {
        foreach ($filesPath as $item) {
            $file = $folder . $item;
            if (is_file($file)) {
                require_once $folder . $item;
            }
        }
    }
}

/**
 * Localize AJAX search data (script bundled in theme.js)
 */
function custom_ajax_search_script()
{
    wp_localize_script('theme-js-bundle', 'themeSearch', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('theme_search_nonce'),
    ]);
}
add_action('wp_enqueue_scripts', 'custom_ajax_search_script');

/**
 * Register custom query vars for search pagination
 */
function lacadev_register_search_query_vars($vars)
{
    $vars[] = 'paged_post';
    $vars[] = 'paged_page';
    $vars[] = 'paged_product';
    $vars[] = 'paged_service';
    // Add more custom post types as needed
    return $vars;
}
add_filter('query_vars', 'lacadev_register_search_query_vars');

// =============================================================================
// CUSTOM POST TYPES
// =============================================================================

new \App\PostTypes\service();
