<?php
/**
 * Gutenberg Blocks Registration
 * 
 * Register ReactJS-based Gutenberg blocks
 * 
 * @package LacaDev
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register Gutenberg blocks scripts and styles
 */
function lacadev_register_gutenberg_blocks_assets() {
    // Use dirname() to go up one level from theme/ directory
    $theme_root = dirname(get_template_directory());
    $asset_file = $theme_root . '/dist/gutenberg/index.asset.php';
    
    if (!file_exists($asset_file)) {
        error_log('Gutenberg blocks asset file not found: ' . $asset_file);
        return;
    }
    
    $asset = require $asset_file;
    
    // For URLs, use get_stylesheet_directory_uri() and go up one level
    $theme_root_uri = dirname(get_stylesheet_directory_uri());
    
    // Register block editor script
    wp_register_script(
        'lacadev-gutenberg-blocks',
        $theme_root_uri . '/dist/gutenberg/index.js',
        $asset['dependencies'],
        $asset['version'],
        false
    );
}
add_action('init', 'lacadev_register_gutenberg_blocks_assets', 5);

/**
 * Register all custom blocks
 */
function lacadev_register_custom_blocks() {
    // First, register assets
    lacadev_register_gutenberg_blocks_assets();
    
    // Get all block directories - use file path, not URL
    $theme_root = dirname(get_template_directory());
    $blocks_dir = $theme_root . '/block-gutenberg';
    
    if (!is_dir($blocks_dir)) {
        error_log('Blocks directory not found: ' . $blocks_dir);
        return;
    }
    
    $blocks = scandir($blocks_dir);
    $registered_count = 0;
    
    foreach ($blocks as $block) {
        // Skip . and .. and index.js
        if ($block === '.' || $block === '..' || $block === 'index.js' || $block === 'debug.js') {
            continue;
        }
        
        $block_json = $blocks_dir . '/' . $block . '/block.json';
        
        if (file_exists($block_json)) {
            // Check if block has render.php for dynamic rendering
            $render_php = $blocks_dir . '/' . $block . '/render.php';
            $block_args = [
                'editor_script' => 'lacadev-gutenberg-blocks',
                // Styles come from theme's compiled CSS (dist/styles/theme.css and dist/styles/editor.css)
                // No need to register block-specific styles
            ];
            
            // Add render callback if render.php exists
            if (file_exists($render_php)) {
                $block_args['render_callback'] = function($attributes, $content) use ($render_php) {
                    ob_start();
                    require $render_php;
                    return ob_get_clean();
                };
            }
            
            $result = register_block_type_from_metadata($block_json, $block_args);
            
            if ($result) {
                $registered_count++;
                error_log('Registered block: ' . $block . (file_exists($render_php) ? ' (dynamic)' : ' (static)'));
            }
        }
    }
    
    error_log('Total blocks registered: ' . $registered_count);
}
add_action('init', 'lacadev_register_custom_blocks', 10);

/**
 * Register custom block category
 */
function lacadev_register_block_category($categories, $post) {
    return array_merge(
        [
            [
                'slug'  => 'lacadev-blocks',
                'title' => __('La Cà Blocks', 'laca'),
                'icon'  => 'admin-customizer',
            ],
        ],
        $categories
    );
}
add_filter('block_categories_all', 'lacadev_register_block_category', 10, 2);
