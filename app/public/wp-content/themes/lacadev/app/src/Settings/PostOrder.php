<?php
/**
 * Custom Post Order
 * Drag & Drop ordering for posts, pages, custom post types, and taxonomies.
 *
 * @package LacaDev
 * @since 1.0.0
 */

namespace App\Settings;

use WP_Screen;

class PostOrder
{
    private string $optionKey = 'scporder_options';

    public function __construct()
    {
        add_action('init', [$this, 'install']);
        add_action('admin_menu', [$this, 'registerSettingsPage']);
        add_action('admin_init', [$this, 'handleSettingsSubmit']);
        add_action('admin_init', [$this, 'normalizeOrders']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets'], 20);

        add_action('wp_ajax_update-menu-order', [$this, 'updateMenuOrder']);
        add_action('wp_ajax_update-menu-order-tags', [$this, 'updateMenuOrderTags']);
        add_action('wp_ajax_scpo_reset_order', [$this, 'resetOrder']);

        add_action('pre_get_posts', [$this, 'preGetPosts']);
        add_filter('get_previous_post_where', [$this, 'previousPostWhere']);
        add_filter('get_previous_post_sort', [$this, 'previousPostSort']);
        add_filter('get_next_post_where', [$this, 'nextPostWhere']);
        add_filter('get_next_post_sort', [$this, 'nextPostSort']);
        add_filter('get_terms_orderby', [$this, 'filterTermsOrderby'], 10, 3);
        add_filter('wp_get_object_terms', [$this, 'sortTerms'], 10, 3);
        add_filter('get_terms', [$this, 'sortTerms'], 10, 3);
    }

    public function install(): void
    {
        if (get_option('scporder_install')) {
            return;
        }

        global $wpdb;
        $column = $wpdb->get_var("SHOW COLUMNS FROM {$wpdb->terms} LIKE 'term_order'");

        if (! $column) {
            $wpdb->query("ALTER TABLE {$wpdb->terms} ADD `term_order` INT(4) NULL DEFAULT '0'");
        }

        update_option('scporder_install', 1);
    }

    public function registerSettingsPage(): void
    {
        add_options_page(
            __('Simple Custom Post Order', 'lacadev'),
            __('Post Order', 'lacadev'),
            'manage_options',
            'scporder-settings',
            [$this, 'renderSettingsPage']
        );
    }

    public function enqueueAdminAssets(): void
    {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        $isSettingsPage = $screen instanceof WP_Screen && $screen->id === 'settings_page_scporder-settings';
        $isSortableScreen = $this->shouldEnableSorting();

        if (! $isSettingsPage && ! $isSortableScreen) {
            return;
        }

        wp_localize_script(
            'theme-admin-js-bundle',
            'lacaPostOrder',
            [
                'ajaxUrl'          => admin_url('admin-ajax.php'),
                'nonce'            => wp_create_nonce('scporder_nonce_action'),
                'resetNonce'       => wp_create_nonce('scpo-reset-order'),
                'isSortableScreen' => $isSortableScreen,
                'isSettingsPage'   => $isSettingsPage,
                'screenBase'       => $screen ? $screen->base : '',
                'taxonomy'         => $screen && isset($screen->taxonomy) ? $screen->taxonomy : '',
                'postType'         => $screen && isset($screen->post_type) ? $screen->post_type : '',
                'postTypes'        => $this->getEnabledPostTypes(),
                'taxonomies'       => $this->getEnabledTaxonomies(),
            ]
        );
    }

    public function renderSettingsPage(): void
    {
        $options        = $this->getOptions();
        $selectedTypes  = $this->getEnabledPostTypes();
        $selectedTax    = $this->getEnabledTaxonomies();
        $advancedView   = isset($options['show_advanced_view']) ? $options['show_advanced_view'] : '';
        $postTypesArgs  = apply_filters(
            'scpo_post_types_args',
            [
                'show_ui'      => true,
                'show_in_menu' => true,
            ],
            $options
        );
        $postTypes      = get_post_types($postTypesArgs, 'objects');
        $taxonomies     = get_taxonomies(['show_ui' => true], 'objects');
        ?>
        <div class="wrap" id="laca-post-order-settings">
            <h1><?php esc_html_e('Simple Custom Post Order Settings', 'lacadev'); ?></h1>
            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'update') : ?>
                <div id="message" class="updated notice is-dismissible">
                    <p><?php esc_html_e('Settings Updated.', 'lacadev'); ?></p>
                </div>
            <?php endif; ?>

            <form method="post">
                <?php wp_nonce_field('nonce_scporder'); ?>
                <div id="scporder_select_objects" class="scporder-box">
                    <table class="form-table">
                        <tbody>
                            <tr valign="top">
                                <th scope="row"><?php esc_html_e('Check to Sort Post Types', 'lacadev'); ?></th>
                                <td>
                                    <label class="scporder-toggle">
                                        <input id="scporder_allcheck_objects" class="epsilon-toggle__input" type="checkbox">
                                        <span class="epsilon-toggle__items">
                                            <span class="epsilon-toggle__track"></span>
                                            <span class="epsilon-toggle__thumb"></span>
                                            <svg class="epsilon-toggle__off" width="6" height="6" aria-hidden="true" focusable="false" viewBox="0 0 6 6">
                                                <path d="M3 1.5c.8 0 1.5.7 1.5 1.5S3.8 4.5 3 4.5 1.5 3.8 1.5 3 2.2 1.5 3 1.5M3 0C1.3 0 0 1.3 0 3s1.3 3 3 3 3-1.3 3-3-1.3-3-3-3z"></path>
                                            </svg>
                                            <svg class="epsilon-toggle__on" width="2" height="6" aria-hidden="true" focusable="false" viewBox="0 0 2 6">
                                                <path d="M0 0h2v6H0z"></path>
                                            </svg>
                                        </span>
                                        <span class="epsilon-toggle__label"><?php esc_html_e('Check All', 'lacadev'); ?></span>
                                    </label>
                                    <?php
                                    foreach ($postTypes as $postType) {
                                        if ($postType->name === 'attachment') {
                                            continue;
                                        }
                                        ?>
                                        <label class="scporder-toggle">
                                            <input
                                                class="epsilon-toggle__input"
                                                type="checkbox"
                                                name="objects[]"
                                                value="<?php echo esc_attr($postType->name); ?>"
                                                <?php checked(in_array($postType->name, $selectedTypes, true)); ?>
                                            >
                                            <span class="epsilon-toggle__items">
                                                <span class="epsilon-toggle__track"></span>
                                                <span class="epsilon-toggle__thumb"></span>
                                                <svg class="epsilon-toggle__off" width="6" height="6" aria-hidden="true" focusable="false" viewBox="0 0 6 6">
                                                    <path d="M3 1.5c.8 0 1.5.7 1.5 1.5S3.8 4.5 3 4.5 1.5 3.8 1.5 3 2.2 1.5 3 1.5M3 0C1.3 0 0 1.3 0 3s1.3 3 3 3 3-1.3 3-3-1.3-3-3-3z"></path>
                                                </svg>
                                                <svg class="epsilon-toggle__on" width="2" height="6" aria-hidden="true" focusable="false" viewBox="0 0 2 6">
                                                    <path d="M0 0h2v6H0z"></path>
                                                </svg>
                                            </span>
                                            <span class="epsilon-toggle__label"><?php echo esc_html($postType->label); ?></span>
                                        </label>
                                        <?php
                                    }
                                    ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div id="scporder_select_tags" class="scporder-box">
                    <table class="form-table">
                        <tbody>
                            <tr valign="top">
                                <th scope="row"><?php esc_html_e('Check to Sort Taxonomies', 'lacadev'); ?></th>
                                <td>
                                    <label class="scporder-toggle">
                                        <input id="scporder_allcheck_tags" class="epsilon-toggle__input" type="checkbox">
                                        <span class="epsilon-toggle__items">
                                            <span class="epsilon-toggle__track"></span>
                                            <span class="epsilon-toggle__thumb"></span>
                                            <svg class="epsilon-toggle__off" width="6" height="6" aria-hidden="true" focusable="false" viewBox="0 0 6 6">
                                                <path d="M3 1.5c.8 0 1.5.7 1.5 1.5S3.8 4.5 3 4.5 1.5 3.8 1.5 3 2.2 1.5 3 1.5M3 0C1.3 0 0 1.3 0 3s1.3 3 3 3 3-1.3 3-3-1.3-3-3-3z"></path>
                                            </svg>
                                            <svg class="epsilon-toggle__on" width="2" height="6" aria-hidden="true" focusable="false" viewBox="0 0 2 6">
                                                <path d="M0 0h2v6H0z"></path>
                                            </svg>
                                        </span>
                                        <span class="epsilon-toggle__label"><?php esc_html_e('Check All', 'lacadev'); ?></span>
                                    </label>
                                    <?php
                                    foreach ($taxonomies as $taxonomy) {
                                        if ($taxonomy->name === 'post_format') {
                                            continue;
                                        }
                                        ?>
                                        <label class="scporder-toggle">
                                            <input
                                                class="epsilon-toggle__input"
                                                type="checkbox"
                                                name="tags[]"
                                                value="<?php echo esc_attr($taxonomy->name); ?>"
                                                <?php checked(in_array($taxonomy->name, $selectedTax, true)); ?>
                                            >
                                            <span class="epsilon-toggle__items">
                                                <span class="epsilon-toggle__track"></span>
                                                <span class="epsilon-toggle__thumb"></span>
                                                <svg class="epsilon-toggle__off" width="6" height="6" aria-hidden="true" focusable="false" viewBox="0 0 6 6">
                                                    <path d="M3 1.5c.8 0 1.5.7 1.5 1.5S3.8 4.5 3 4.5 1.5 3.8 1.5 3 2.2 1.5 3 1.5M3 0C1.3 0 0 1.3 0 3s1.3 3 3 3 3-1.3 3-3-1.3-3-3-3z"></path>
                                                </svg>
                                                <svg class="epsilon-toggle__on" width="2" height="6" aria-hidden="true" focusable="false" viewBox="0 0 2 6">
                                                    <path d="M0 0h2v6H0z"></path>
                                                </svg>
                                            </span>
                                            <span class="epsilon-toggle__label"><?php echo esc_html($taxonomy->label); ?></span>
                                        </label>
                                        <?php
                                    }
                                    ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div id="scporder_advanced_view" class="scporder-box">
                    <table class="form-table">
                        <tbody>
                            <tr valign="top">
                                <th scope="row"><?php esc_html_e('Check to see advanced view of Post Types', 'lacadev'); ?></th>
                                <td>
                                    <label class="scporder-toggle">
                                        <input
                                            class="epsilon-toggle__input"
                                            type="checkbox"
                                            name="show_advanced_view"
                                            value="1"
                                            <?php checked('1', $advancedView); ?>
                                        >
                                        <span class="epsilon-toggle__items">
                                            <span class="epsilon-toggle__track"></span>
                                            <span class="epsilon-toggle__thumb"></span>
                                            <svg class="epsilon-toggle__off" width="6" height="6" aria-hidden="true" focusable="false" viewBox="0 0 6 6">
                                                <path d="M3 1.5c.8 0 1.5.7 1.5 1.5S3.8 4.5 3 4.5 1.5 3.8 1.5 3 2.2 1.5 3 1.5M3 0C1.3 0 0 1.3 0 3s1.3 3 3 3 3-1.3 3-3-1.3-3-3-3z"></path>
                                            </svg>
                                            <svg class="epsilon-toggle__on" width="2" height="6" aria-hidden="true" focusable="false" viewBox="0 0 2 6">
                                                <path d="M0 0h2v6H0z"></path>
                                            </svg>
                                        </span>
                                        <span class="epsilon-toggle__label">
                                            <?php echo esc_html__('Show advanced view of Post Types', 'lacadev'); ?>
                                        </span>
                                    </label>
                                    <p class="description">
                                        <?php esc_html_e('NOTICE: This is for advanced users only. Enabling this will include post types that are hidden from menus.', 'lacadev'); ?>
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <p class="submit">
                    <input type="submit" class="button-primary" name="scporder_submit" value="<?php esc_attr_e('Update', 'lacadev'); ?>">
                </p>
            </form>

            <div class="scpo-reset-order" data-reset-nonce="<?php echo esc_attr(wp_create_nonce('scpo-reset-order')); ?>">
                <h2><?php esc_html_e('Reset order of the posts?', 'lacadev'); ?></h2>
                <div id="scpo_reset_select_objects">
                    <table class="form-table">
                        <tbody>
                            <tr valign="top">
                                <th scope="row"><?php esc_html_e('Check to reset order of Post Types', 'lacadev'); ?></th>
                                <td>
                                    <?php
                                    foreach ($postTypes as $postType) {
                                        if ($postType->name === 'attachment') {
                                            continue;
                                        }
                                        ?>
                                        <label class="scporder-toggle">
                                            <input class="epsilon-toggle__input" type="checkbox" name="<?php echo esc_attr($postType->name); ?>" value="">
                                            <span class="epsilon-toggle__items">
                                                <span class="epsilon-toggle__track"></span>
                                                <span class="epsilon-toggle__thumb"></span>
                                                <svg class="epsilon-toggle__off" width="6" height="6" aria-hidden="true" focusable="false" viewBox="0 0 6 6">
                                                    <path d="M3 1.5c.8 0 1.5.7 1.5 1.5S3.8 4.5 3 4.5 1.5 3.8 1.5 3 2.2 1.5 3 1.5M3 0C1.3 0 0 1.3 0 3s1.3 3 3 3 3-1.3 3-3-1.3-3-3-3z"></path>
                                                </svg>
                                                <svg class="epsilon-toggle__on" width="2" height="6" aria-hidden="true" focusable="false" viewBox="0 0 2 6">
                                                    <path d="M0 0h2v6H0z"></path>
                                                </svg>
                                            </span>
                                            <span class="epsilon-toggle__label"><?php echo esc_html($postType->label); ?></span>
                                        </label>
                                        <?php
                                    }
                                    ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="scpo-reset-actions">
                    <a id="reset-scp-order" class="button button-primary" href="#"><?php esc_html_e('Reset order', 'lacadev'); ?></a>
                    <span class="scpo-reset-response" aria-live="polite"></span>
                </div>
            </div>
        </div>
        <?php
    }

    public function handleSettingsSubmit(): void
    {
        if (! isset($_POST['scporder_submit'])) {
            return;
        }

        check_admin_referer('nonce_scporder');

        $inputOptions                       = [];
        $inputOptions['objects']            = isset($_POST['objects']) && is_array($_POST['objects']) ? array_map('sanitize_text_field', (array) $_POST['objects']) : [];
        $inputOptions['tags']               = isset($_POST['tags']) && is_array($_POST['tags']) ? array_map('sanitize_text_field', (array) $_POST['tags']) : [];
        $inputOptions['show_advanced_view'] = isset($_POST['show_advanced_view']) ? '1' : '';

        update_option($this->optionKey, $inputOptions);

        $this->seedPostOrder($inputOptions['objects']);
        $this->seedTaxonomyOrder($inputOptions['tags']);

        wp_safe_redirect(admin_url('options-general.php?page=scporder-settings&msg=update'));
        exit();
    }

    public function normalizeOrders(): void
    {
        if ($this->isDoingAjax()) {
            return;
        }

        global $wpdb;
        $objects = $this->getEnabledPostTypes();
        $tags    = $this->getEnabledTaxonomies();

        foreach ($objects as $object) {
            $query = $wpdb->prepare(
                "SELECT COUNT(*) AS cnt, MAX(menu_order) AS max_order FROM {$wpdb->posts} WHERE post_type = %s AND post_status IN ('publish', 'pending', 'draft', 'private', 'future')",
                $object
            );
            $stats = $wpdb->get_row($query);

            if (! $stats || (int) $stats->cnt === 0 || (int) $stats->cnt === (int) $stats->max_order) {
                continue;
            }

            $wpdb->query('SET @row_number = 0;');
            $wpdb->query(
                $wpdb->prepare(
                    "UPDATE {$wpdb->posts} AS pt
                    JOIN (
                        SELECT ID, (@row_number:=@row_number + 1) AS row_num
                        FROM {$wpdb->posts}
                        WHERE post_type = %s AND post_status IN ('publish', 'pending', 'draft', 'private', 'future')
                        ORDER BY menu_order ASC
                    ) AS pt2 ON pt.ID = pt2.ID
                    SET pt.menu_order = pt2.row_num;",
                    $object
                )
            );
        }

        foreach ($tags as $taxonomy) {
            $query = $wpdb->prepare(
                "SELECT COUNT(*) AS cnt, MAX(term_order) AS max_order FROM {$wpdb->terms} AS terms
                INNER JOIN {$wpdb->term_taxonomy} AS tax ON terms.term_id = tax.term_id
                WHERE tax.taxonomy = %s",
                $taxonomy
            );
            $stats = $wpdb->get_row($query);

            if (! $stats || (int) $stats->cnt === 0 || (int) $stats->cnt === (int) $stats->max_order) {
                continue;
            }

            $results = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT terms.term_id
                    FROM {$wpdb->terms} AS terms
                    INNER JOIN {$wpdb->term_taxonomy} AS tax ON terms.term_id = tax.term_id
                    WHERE tax.taxonomy = %s
                    ORDER BY term_order ASC",
                    $taxonomy
                )
            );

            foreach ($results as $key => $result) {
                $wpdb->update($wpdb->terms, ['term_order' => $key + 1], ['term_id' => $result->term_id]);
            }
        }
    }

    public function updateMenuOrder(): void
    {
        if (! check_ajax_referer('scporder_nonce_action', 'nonce', false)) {
            wp_send_json_error(['message' => 'Invalid nonce'], 403);
        }

        if (! current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Insufficient permissions'], 403);
        }

        $rawOrder = isset($_POST['order']) ? wp_unslash($_POST['order']) : '';
        parse_str($rawOrder, $data);

        if (! is_array($data)) {
            wp_send_json_error(['message' => 'Invalid payload'], 400);
        }

        global $wpdb;

        $idList = [];
        foreach ($data as $values) {
            foreach ($values as $id) {
                $idList[] = (int) $id;
            }
        }

        if (empty($idList)) {
            wp_send_json_error(['message' => 'Empty payload'], 400);
        }

        $menuOrderValues = [];
        foreach ($idList as $id) {
            $menuOrderValues[] = (int) $wpdb->get_var($wpdb->prepare("SELECT menu_order FROM {$wpdb->posts} WHERE ID = %d", $id));
        }

        sort($menuOrderValues);

        $position = 0;
        foreach ($data as $values) {
            foreach ($values as $id) {
                $id = (int) $id;
                $wpdb->update(
                    $wpdb->posts,
                    ['menu_order' => $menuOrderValues[$position] ?? $position],
                    ['ID' => $id],
                    ['%d'],
                    ['%d']
                );
                $position++;
            }
        }

        wp_cache_flush();
        do_action('scp_update_menu_order');

        wp_send_json_success();
    }

    public function updateMenuOrderTags(): void
    {
        if (! check_ajax_referer('scporder_nonce_action', 'nonce', false)) {
            wp_send_json_error(['message' => 'Invalid nonce'], 403);
        }

        if (! current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Insufficient permissions'], 403);
        }

        $rawOrder = isset($_POST['order']) ? wp_unslash($_POST['order']) : '';
        parse_str($rawOrder, $data);

        if (! is_array($data)) {
            wp_send_json_error(['message' => 'Invalid payload'], 400);
        }

        global $wpdb;
        $idList = [];
        foreach ($data as $values) {
            foreach ($values as $id) {
                $idList[] = (int) $id;
            }
        }

        if (empty($idList)) {
            wp_send_json_error(['message' => 'Empty payload'], 400);
        }

        $menuOrderValues = [];
        foreach ($idList as $id) {
            $menuOrderValues[] = (int) $wpdb->get_var($wpdb->prepare("SELECT term_order FROM {$wpdb->terms} WHERE term_id = %d", $id));
        }

        sort($menuOrderValues);

        $position = 0;
        foreach ($data as $values) {
            foreach ($values as $id) {
                $id = (int) $id;
                $wpdb->update(
                    $wpdb->terms,
                    ['term_order' => $menuOrderValues[$position] ?? $position],
                    ['term_id' => $id],
                    ['%d'],
                    ['%d']
                );
                $position++;
            }
        }

        wp_cache_flush();
        do_action('scp_update_menu_order_tags');

        wp_send_json_success();
    }

    public function resetOrder(): void
    {
        if (! check_ajax_referer('scpo-reset-order', 'scpo_security', false)) {
            wp_send_json_error(['message' => 'Invalid nonce'], 403);
        }

        if (! current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions'], 403);
        }

        $items = isset($_POST['items']) && is_array($_POST['items']) ? array_map('sanitize_text_field', (array) $_POST['items']) : [];

        if (empty($items)) {
            wp_send_json_error(['message' => 'Nothing selected'], 400);
        }

        global $wpdb;
        $placeholders = implode(',', array_fill(0, count($items), '%s'));
        $query        = $wpdb->prepare("UPDATE {$wpdb->posts} SET menu_order = 0 WHERE post_type IN ({$placeholders})", $items);
        $result       = $wpdb->query($query);

        $options = $this->getOptions();
        if ($result !== false && isset($options['objects']) && is_array($options['objects'])) {
            $options['objects'] = array_values(array_diff($options['objects'], $items));
            update_option($this->optionKey, $options);
        }

        if ($result !== false) {
            wp_send_json_success(__('Items have been reset', 'lacadev'));
        }

        wp_send_json_error(['message' => __('Reset failed', 'lacadev')], 500);
    }

    public function previousPostWhere(string $where): string
    {
        global $post;
        $objects = $this->getEnabledPostTypes();

        if (! isset($post->post_type) || ! in_array($post->post_type, $objects, true)) {
            return $where;
        }

        return preg_replace("/p.post_date < '[0-9\-\s:]+'/i", "p.menu_order > '{$post->menu_order}'", $where);
    }

    public function previousPostSort(string $orderby): string
    {
        global $post;
        $objects = $this->getEnabledPostTypes();

        if (isset($post->post_type) && in_array($post->post_type, $objects, true)) {
            return 'ORDER BY p.menu_order ASC LIMIT 1';
        }

        return $orderby;
    }

    public function nextPostWhere(string $where): string
    {
        global $post;
        $objects = $this->getEnabledPostTypes();

        if (! isset($post->post_type) || ! in_array($post->post_type, $objects, true)) {
            return $where;
        }

        return preg_replace("/p.post_date > '[0-9\-\s:]+'/i", "p.menu_order < '{$post->menu_order}'", $where);
    }

    public function nextPostSort(string $orderby): string
    {
        global $post;
        $objects = $this->getEnabledPostTypes();

        if (isset($post->post_type) && in_array($post->post_type, $objects, true)) {
            return 'ORDER BY p.menu_order DESC LIMIT 1';
        }

        return $orderby;
    }

    public function preGetPosts(\WP_Query $query)
    {
        $objects = $this->getEnabledPostTypes();

        if (empty($objects) || $query->is_search()) {
            return $query;
        }

        if (is_admin() && ! wp_doing_ajax()) {
            if (isset($query->query['post_type']) && ! isset($_GET['orderby'])) {
                if (in_array($query->query['post_type'], $objects, true)) {
                    if (! $query->get('orderby')) {
                        $query->set('orderby', 'menu_order');
                    }
                    if (! $query->get('order')) {
                        $query->set('order', 'ASC');
                    }
                }
            }
        } else {
            $active = false;

            if (isset($query->query['post_type'])) {
                if (! is_array($query->query['post_type']) && in_array($query->query['post_type'], $objects, true)) {
                    $active = true;
                }
            } elseif (in_array('post', $objects, true)) {
                $active = true;
            }

            if (! $active) {
                return $query;
            }

            if ($query->get('orderby') === 'date') {
                $query->set('orderby', 'menu_order');
            }
            if ($query->get('order') === 'DESC') {
                $query->set('order', 'ASC');
            }
            if (! $query->get('orderby')) {
                $query->set('orderby', 'menu_order');
            }
            if (! $query->get('order')) {
                $query->set('order', 'ASC');
            }
        }

        return $query;
    }

    public function filterTermsOrderby($orderby, $args, $taxonomies): string
    {
        if (is_admin() && ! wp_doing_ajax()) {
            return $orderby;
        }

        $enabled = $this->getEnabledTaxonomies();
        $taxonomy = is_array($taxonomies) && isset($taxonomies[0]) ? $taxonomies[0] : $taxonomies;

        if (! $taxonomy || ! in_array($taxonomy, $enabled, true)) {
            return $orderby;
        }

        return 't.term_order';
    }

    public function sortTerms($terms)
    {
        $enabled = $this->getEnabledTaxonomies();

        foreach ((array) $terms as $term) {
            if (is_object($term) && isset($term->taxonomy) && ! in_array($term->taxonomy, $enabled, true)) {
                return $terms;
            }
        }

        if (is_array($terms)) {
            usort(
                $terms,
                static function ($a, $b) {
                    if ($a->term_order === $b->term_order) {
                        return 0;
                    }

                    return ($a->term_order < $b->term_order) ? -1 : 1;
                }
            );
        }

        return $terms;
    }

    private function getOptions(): array
    {
        $options = get_option($this->optionKey);

        if (! is_array($options)) {
            return [
                'objects'            => [],
                'tags'               => [],
                'show_advanced_view' => '',
            ];
        }

        $options['objects'] = isset($options['objects']) && is_array($options['objects']) ? $options['objects'] : [];
        $options['tags']    = isset($options['tags']) && is_array($options['tags']) ? $options['tags'] : [];

        return $options;
    }

    private function getEnabledPostTypes(): array
    {
        $options = $this->getOptions();

        return isset($options['objects']) && is_array($options['objects']) ? $options['objects'] : [];
    }

    private function getEnabledTaxonomies(): array
    {
        $options = $this->getOptions();

        return isset($options['tags']) && is_array($options['tags']) ? $options['tags'] : [];
    }

    private function seedPostOrder(array $objects): void
    {
        global $wpdb;

        foreach ($objects as $object) {
            $object = sanitize_text_field($object);
            $count  = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s AND post_status IN ('publish', 'pending', 'draft', 'private', 'future')",
                    $object
                )
            );

            if ($count === 0) {
                continue;
            }

            $orderBy = $object === 'page' ? 'post_title ASC' : 'post_date DESC';
            $results = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_status IN ('publish', 'pending', 'draft', 'private', 'future') ORDER BY {$orderBy}",
                    $object
                )
            );

            foreach ($results as $key => $result) {
                $wpdb->update($wpdb->posts, ['menu_order' => $key + 1], ['ID' => $result->ID]);
            }
        }
    }

    private function seedTaxonomyOrder(array $taxonomies): void
    {
        global $wpdb;

        foreach ($taxonomies as $taxonomy) {
            $taxonomy = sanitize_text_field($taxonomy);
            $count    = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->terms} AS terms
                    INNER JOIN {$wpdb->term_taxonomy} AS tax ON terms.term_id = tax.term_id
                    WHERE tax.taxonomy = %s",
                    $taxonomy
                )
            );

            if ($count === 0) {
                continue;
            }

            $results = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT terms.term_id
                    FROM {$wpdb->terms} AS terms
                    INNER JOIN {$wpdb->term_taxonomy} AS tax ON terms.term_id = tax.term_id
                    WHERE tax.taxonomy = %s
                    ORDER BY name ASC",
                    $taxonomy
                )
            );

            foreach ($results as $key => $result) {
                $wpdb->update($wpdb->terms, ['term_order' => $key + 1], ['term_id' => $result->term_id]);
            }
        }
    }

    private function shouldEnableSorting(): bool
    {
        $objects = $this->getEnabledPostTypes();
        $tags    = $this->getEnabledTaxonomies();

        if (empty($objects) && empty($tags)) {
            return false;
        }

        if (
            isset($_GET['orderby']) ||
            strpos($_SERVER['REQUEST_URI'], 'action=edit') !== false ||
            strpos($_SERVER['REQUEST_URI'], 'wp-admin/post-new.php') !== false
        ) {
            return false;
        }

        $screen = function_exists('get_current_screen') ? get_current_screen() : null;

        if ($screen instanceof WP_Screen && $screen->base === 'edit') {
            $postType = $screen->post_type ?: 'post';

            if (in_array($postType, $objects, true)) {
                return true;
            }
        }

        if ($screen instanceof WP_Screen && $screen->base === 'edit-tags' && isset($screen->taxonomy)) {
            if (in_array($screen->taxonomy, $tags, true)) {
                return true;
            }
        }

        if (! $screen && isset($_GET['taxonomy']) && in_array(sanitize_text_field($_GET['taxonomy']), $tags, true)) {
            return true;
        }

        return false;
    }

    private function isDoingAjax(): bool
    {
        if (function_exists('wp_doing_ajax')) {
            return wp_doing_ajax();
        }

        return defined('DOING_AJAX') && DOING_AJAX;
    }
}
