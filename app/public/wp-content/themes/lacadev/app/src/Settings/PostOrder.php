<?php
/**
 * Custom Post Order
 * 
 * Drag & Drop ordering for posts, pages, custom post types, and taxonomies
 * 
 * @package LacaDev
 * @since 1.0.0
 */

namespace App\Settings;

class PostOrder {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Database setup
        add_action('init', [$this, 'checkDatabase']);
        
        // Admin menu - Priority 100 to appear after Tools and Login Socials
        add_action('admin_menu', [$this, 'addAdminMenu'], 100);
        
        // Load scripts
        add_action('admin_enqueue_scripts', [$this, 'enqueueScripts']);
        
        // AJAX handlers
        add_action('wp_ajax_update_post_order', [$this, 'updatePostOrder']);
        add_action('wp_ajax_update_term_order', [$this, 'updateTermOrder']);
        
        // Frontend filters
        add_action('pre_get_posts', [$this, 'modifyQuery']);
        add_filter('posts_orderby', [$this, 'modifyOrderBy'], 10, 2);
        add_filter('get_terms_orderby', [$this, 'modifyTermsOrderBy'], 10, 3);
    }
    
    /**
     * Check and create term_order column if not exists
     */
    public function checkDatabase() {
        global $wpdb;
        
        if (!get_option('lacadev_post_order_installed')) {
            $result = $wpdb->query("DESCRIBE {$wpdb->terms} `term_order`");
            
            if (!$result) {
                $wpdb->query(
                    "ALTER TABLE {$wpdb->terms} 
                    ADD `term_order` INT(4) NULL DEFAULT '0'"
                );
            }
            
            update_option('lacadev_post_order_installed', 1);
        }
    }
    
    /**
     * Add submenu under Laca Admin
     */
    public function addAdminMenu() {
        add_submenu_page(
            'laca-admin',
            __('Post Order', 'laca'),
            __('Post Order', 'laca'),
            'manage_options',
            'laca-post-order',
            [$this, 'renderSettingsPage']
        );
    }
    
    /**
     * Enqueue scripts only on relevant pages
     */
    public function enqueueScripts($hook) {
        // Settings page
        if ($hook === 'laca-admin_page_laca-post-order') {
            wp_enqueue_style(
                'laca-admin-css',
                get_template_directory_uri() . '/dist/admin.css',
                [],
                wp_get_theme()->get('Version')
            );
        }
        
        // Post/Term list pages
        if ($this->shouldLoadSortable()) {
            $handle = 'theme-admin-js-bundle';
            
            // Check if main admin script is enqueued
            if (!wp_script_is($handle, 'enqueued')) {
                // If not, enqueue it (though it should be by assets.php)
                wp_enqueue_script(
                    $handle,
                    get_template_directory_uri() . '/dist/admin.js',
                    [],
                    wp_get_theme()->get('Version'),
                    true
                );
            }
            
            // Localize script
            wp_localize_script($handle, 'lacaPostOrder', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('laca_post_order_nonce'),
            ]);
        }
    }
    
    /**
     * Check if we should load sortable on current page
     */
    private function shouldLoadSortable() {
        $enabled_objects = $this->getEnabledObjects();
        $enabled_terms = $this->getEnabledTerms();
        
        if (empty($enabled_objects) && empty($enabled_terms)) {
            error_log("PostOrder shouldLoadSortable: No enabled objects/terms");
            return false;
        }
        
        // Don't load if custom orderby is set
        if (isset($_GET['orderby'])) {
            error_log("PostOrder shouldLoadSortable: Custom orderby set, not loading");
            return false;
        }
        
        // Check if we're on a post list page
        if (isset($_GET['post_type']) && in_array($_GET['post_type'], $enabled_objects)) {
            error_log("PostOrder shouldLoadSortable: Loading for post_type=" . $_GET['post_type']);
            return true;
        }
        
        // Check if we're on default post list
        if (!isset($_GET['post_type']) && strstr($_SERVER['REQUEST_URI'], 'wp-admin/edit.php')) {
            $should_load = in_array('post', $enabled_objects);
            error_log("PostOrder shouldLoadSortable: Default post list, should_load=" . ($should_load ? 'yes' : 'no'));
            return $should_load;
        }
        
        // Check if we're on a taxonomy page
        if (isset($_GET['taxonomy']) && in_array($_GET['taxonomy'], $enabled_terms)) {
            error_log("PostOrder shouldLoadSortable: Loading for taxonomy=" . $_GET['taxonomy']);
            return true;
        }
        
        $current_post_type = isset($_GET['post_type']) ? $_GET['post_type'] : 'not_set';
        error_log("PostOrder shouldLoadSortable: Not loading, post_type={$current_post_type}, enabled=" . json_encode($enabled_objects));
        return false;
    }
    
    /**
     * AJAX: Update post order
     */
    public function updatePostOrder() {
        check_ajax_referer('laca_post_order_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_die('Unauthorized', 403);
        }
        
        global $wpdb;
        parse_str($_POST['order'], $data);
        
        if (!is_array($data) || empty($data['post'])) {
            wp_die('Invalid data', 400);
        }
        
        $ids = array_map('intval', $data['post']);
        
        // Update with new order (sequential)
        foreach ($ids as $position => $id) {
            $wpdb->update(
                $wpdb->posts,
                ['menu_order' => $position],
                ['ID' => $id],
                ['%d'],
                ['%d']
            );
        }
        
        wp_cache_flush();
        wp_die('success');
    }
    
    /**
     * AJAX: Update term order
     */
    public function updateTermOrder() {
        check_ajax_referer('laca_post_order_nonce', 'nonce');
        
        if (!current_user_can('manage_categories')) {
            wp_die('Unauthorized', 403);
        }
        
        global $wpdb;
        parse_str($_POST['order'], $data);
        
        if (!is_array($data) || empty($data['post'])) {
            wp_die('Invalid data', 400);
        }
        
        $ids = array_map('intval', $data['post']);
        
        // Update with new order (sequential)
        foreach ($ids as $position => $id) {
            $wpdb->update(
                $wpdb->terms,
                ['term_order' => $position],
                ['term_id' => $id],
                ['%d'],
                ['%d']
            );
        }
        
        wp_cache_flush();
        wp_die('success');
    }
    
    /**
     * Modify WP_Query to order by menu_order
     */
    public function modifyQuery($wp_query) {
        $enabled_objects = $this->getEnabledObjects();
        
        if (empty($enabled_objects) || is_search()) {
            return;
        }
        
        $should_apply = false;
        
        // Debug
        $post_type = isset($wp_query->query['post_type']) ? $wp_query->query['post_type'] : 'not_set';
        $is_admin = is_admin() && !wp_doing_ajax();
        error_log("PostOrder modifyQuery: post_type={$post_type}, is_admin={$is_admin}, enabled=" . json_encode($enabled_objects));
        
        // Admin
        if (is_admin() && !wp_doing_ajax()) {
            if (isset($wp_query->query['post_type']) && !isset($_GET['orderby'])) {
                if (in_array($wp_query->query['post_type'], $enabled_objects)) {
                    $should_apply = true;
                    error_log("PostOrder: ADMIN - Should apply for {$wp_query->query['post_type']}");
                }
            }
        }
        // Frontend
        else {
            if (isset($wp_query->query['post_type'])) {
                if (in_array($wp_query->query['post_type'], $enabled_objects)) {
                    $should_apply = true;
                    error_log("PostOrder: FRONTEND - Should apply for {$wp_query->query['post_type']}");
                }
            } elseif (in_array('post', $enabled_objects)) {
                $should_apply = true;
                error_log("PostOrder: FRONTEND - Should apply for default post");
            }
        }
        
        if ($should_apply) {
            $current_orderby = $wp_query->get('orderby');
            // Only override if orderby is not already set, or if it's the default menu_order
            if (!$current_orderby || $current_orderby === 'menu_order' || $current_orderby === 'menu_order title' || (is_array($current_orderby) && isset($current_orderby['menu_order']))) {
                // Mark this query for custom ordering
                $wp_query->set('_lacadev_custom_order', true);
                error_log("PostOrder: Marked query for custom order");
            } else {
                error_log("PostOrder: NOT marking - custom orderby already set: " . (is_array($current_orderby) ? json_encode($current_orderby) : $current_orderby));
            }
        } else {
            // Not enabled - check if we need to override default WordPress ordering
            $current_orderby = $wp_query->get('orderby');
            $post_type = isset($wp_query->query['post_type']) ? $wp_query->query['post_type'] : '';
            
            // WordPress defaults pages to 'menu_order title', override to date DESC
            if ($post_type === 'page' && $current_orderby === 'menu_order title') {
                $wp_query->set('orderby', 'date');
                $wp_query->set('order', 'DESC');
                error_log("PostOrder: Overriding page default to date DESC");
            } else {
                error_log("PostOrder: NOT marking - should_apply=false, post_type={$post_type}");
            }
        }
    }
    
    /**
     * Modify the ORDER BY SQL clause
     */
    public function modifyOrderBy($orderby, $wp_query) {
        global $wpdb;
        
        // Only apply to queries we marked
        if (!$wp_query->get('_lacadev_custom_order')) {
            return $orderby;
        }
        
        // Debug logging
        $post_type = isset($wp_query->query['post_type']) ? $wp_query->query['post_type'] : 'default';
        error_log("PostOrder: Modifying ORDER BY for post_type: " . $post_type);
        error_log("PostOrder: Original orderby: " . $orderby);
        
        // Order by menu_order ASC, then by post_date DESC
        $new_orderby = "{$wpdb->posts}.menu_order ASC, {$wpdb->posts}.post_date DESC";
        error_log("PostOrder: New orderby: " . $new_orderby);
        
        return $new_orderby;
    }
    
    /**
     * Modify terms orderby
     */
    public function modifyTermsOrderBy($orderby, $args, $taxonomies) {
        if (is_admin() && !wp_doing_ajax()) {
            return $orderby;
        }
        
        $enabled_terms = $this->getEnabledTerms();
        
        if (!isset($args['taxonomy'])) {
            return $orderby;
        }
        
        $taxonomy = is_array($args['taxonomy']) ? $args['taxonomy'][0] : $args['taxonomy'];
        
        if (in_array($taxonomy, $enabled_terms)) {
            $orderby = 't.term_order';
        }
        
        return $orderby;
    }
    
    /**
     * Get enabled post types from options
     */
    private function getEnabledObjects() {
        $options = get_option('laca_post_order_options', []);
        return isset($options['objects']) && is_array($options['objects']) 
            ? $options['objects'] 
            : [];
    }
    
    /**
     * Get enabled taxonomies from options
     */
    private function getEnabledTerms() {
        $options = get_option('laca_post_order_options', []);
        return isset($options['terms']) && is_array($options['terms']) 
            ? $options['terms'] 
            : [];
    }
    
    /**
     * Render settings page
     */
    public function renderSettingsPage() {
        // Save settings
        if (isset($_POST['laca_post_order_submit'])) {
            check_admin_referer('laca_post_order_settings');
            
            $options = [
                'objects' => isset($_POST['objects']) ? array_map('sanitize_text_field', $_POST['objects']) : [],
                'terms' => isset($_POST['terms']) ? array_map('sanitize_text_field', $_POST['terms']) : [],
            ];
            
            update_option('laca_post_order_options', $options);
            
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Settings saved!', 'laca') . '</p></div>';
        }
        
        $current_options = get_option('laca_post_order_options', []);
        $enabled_objects = isset($current_options['objects']) ? $current_options['objects'] : [];
        $enabled_terms = isset($current_options['terms']) ? $current_options['terms'] : [];
        
        // Get all public post types
        $post_types = get_post_types(['public' => true], 'objects');
        
        // Get all public taxonomies
        $taxonomies = get_taxonomies(['public' => true], 'objects');
        ?>
        
        <div class="wrap">
            <h1><?php esc_html_e('Custom Post Order Settings', 'laca'); ?></h1>
            <p><?php esc_html_e('Enable drag & drop ordering for posts, pages, custom post types, and taxonomies.', 'laca'); ?></p>
            
            <form method="post" action="">
                <?php wp_nonce_field('laca_post_order_settings'); ?>
                
                <h2><?php esc_html_e('Post Types', 'laca'); ?></h2>
                <p class="description"><?php esc_html_e('Select which post types should have drag & drop ordering:', 'laca'); ?></p>
                
                <table class="form-table">
                    <tbody>
                        <?php foreach ($post_types as $post_type): ?>
                            <tr>
                                <th scope="row">
                                    <label for="object_<?php echo esc_attr($post_type->name); ?>">
                                        <?php echo esc_html($post_type->label); ?>
                                    </label>
                                </th>
                                <td>
                                    <input type="checkbox" 
                                           name="objects[]" 
                                           id="object_<?php echo esc_attr($post_type->name); ?>"
                                           value="<?php echo esc_attr($post_type->name); ?>"
                                           <?php checked(in_array($post_type->name, $enabled_objects)); ?>>
                                    <span class="description"><?php echo esc_html($post_type->description ?: $post_type->name); ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <h2><?php esc_html_e('Taxonomies', 'laca'); ?></h2>
                <p class="description"><?php esc_html_e('Select which taxonomies should have drag & drop ordering:', 'laca'); ?></p>
                
                <table class="form-table">
                    <tbody>
                        <?php foreach ($taxonomies as $taxonomy): ?>
                            <tr>
                                <th scope="row">
                                    <label for="term_<?php echo esc_attr($taxonomy->name); ?>">
                                        <?php echo esc_html($taxonomy->label); ?>
                                    </label>
                                </th>
                                <td>
                                    <input type="checkbox" 
                                           name="terms[]" 
                                           id="term_<?php echo esc_attr($taxonomy->name); ?>"
                                           value="<?php echo esc_attr($taxonomy->name); ?>"
                                           <?php checked(in_array($taxonomy->name, $enabled_terms)); ?>>
                                    <span class="description"><?php echo esc_html($taxonomy->description ?: $taxonomy->name); ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php submit_button(__('Save Settings', 'laca'), 'primary', 'laca_post_order_submit'); ?>
            </form>
        </div>
        
        <?php
    }
}
