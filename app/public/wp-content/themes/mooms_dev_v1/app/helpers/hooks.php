<?php
/**
 * Thay đổi default order post của WP_Query
 */
add_action('pre_get_posts', static function ($query) {
    /**
     * @var \WP_Query $query
     */
    if (is_admin()) {
        return; // Do not alter sort order in admin lists
    }
    if ($query->is_main_query()) {
        $query->set('orderby', 'menu_order');
        $query->set('order', 'ASC');
    }
});
