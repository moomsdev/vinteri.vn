<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Rate limiting helper for AJAX requests
 */
function lacadev_check_rate_limit($action_name, $limit = 20, $period = 60) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $transient_key = 'rate_limit_' . $action_name . '_' . md5($ip);
    $request_count = get_transient($transient_key);
    
    if ($request_count === false) {
        set_transient($transient_key, 1, $period);
        return true;
    }
    
    if ($request_count >= $limit) {
        wp_send_json_error([
            'message' => __('Quá nhiều requests. Vui lòng thử lại sau.', 'laca')
        ], 429);
        exit;
    }
    
    set_transient($transient_key, $request_count + 1, $period);
    return true;
}

/**
 * Improve search: ONLY search in title, accent-insensitive (Vietnamese support)
 */
function lacadev_improve_search_relevance($search, $wp_query) {
    global $wpdb;
    
    if (empty($wp_query->query_vars['s'])) {
        return $search;
    }
    
    $search_term = $wpdb->esc_like($wp_query->query_vars['s']);
    
    // Search ONLY in post_title with accent-insensitive collation
    // utf8mb4_unicode_ci ignores accents: "se" matches "sẽ", "sê", "sế", etc.
    $search = " AND ({$wpdb->posts}.post_title COLLATE utf8mb4_unicode_ci LIKE '%{$search_term}%')";
    
    return $search;
}

/**
 * AJAX Search Handler
 */
add_action('wp_ajax_nopriv_ajax_search', 'mms_ajax_search');
add_action('wp_ajax_ajax_search', 'mms_ajax_search');

function mms_ajax_search() {
    // Rate limiting: 20 requests per minute
    lacadev_check_rate_limit('ajax_search', 20, 60);
    
    // Security check
    check_ajax_referer('theme_search_nonce', 'nonce');

    // Get search query
    $search_query = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
    
    // Add search relevance filter (title-only, accent-insensitive)
    add_filter('posts_search', 'lacadev_improve_search_relevance', 10, 2);
    
    $html = '';
    $has_results = false;
    
    // Get all public post types
    $post_types = get_post_types(['public' => true], 'objects');
    
    // Organize post types by category
    $organized_types = [
        'product' => [],
        'post' => [],
        'page' => [],
        'other' => []
    ];
    
    foreach ($post_types as $post_type) {
        $type_name = $post_type->name;
        
        // Skip attachments
        if ($type_name === 'attachment') {
            continue;
        }
        
        // Categorize
        if ($type_name === 'product') {
            $organized_types['product'][] = $type_name;
        } elseif ($type_name === 'post') {
            $organized_types['post'][] = $type_name;
        } elseif ($type_name === 'page') {
            $organized_types['page'][] = $type_name;
        } else {
            $organized_types['other'][] = $type_name;
        }
    }
    
    // Search Products (WooCommerce)
    if (!empty($organized_types['product']) && class_exists('WooCommerce')) {
        // First, get total count
        $products_count_query = new WP_Query([
            'post_type' => 'product',
            'posts_per_page' => -1,
            's' => $search_query,
            'post_status' => 'publish',
            'fields' => 'ids', // Only get IDs for counting
        ]);
        $total_products = $products_count_query->found_posts;
        wp_reset_postdata();
        
        // Then get limited results
        $products = new WP_Query([
            'post_type' => 'product',
            'posts_per_page' => 2, // Only show 2 items
            's' => $search_query,
            'post_status' => 'publish',
            'no_found_rows' => false,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);
        
        if ($products->have_posts()) {
            $has_results = true;
            $displayed_count = $products->post_count;
            
            $html .= '<div class="search-results__section">';
            $html .= '<h3 class="search-results__title"><strong>Sản phẩm liên quan</strong> <span class="search-results__count">(hiển thị ' . $displayed_count . '/' . $total_products . ' sản phẩm)</span>:</h3>';
            $html .= '<div class="search-results__list">';
            
            while ($products->have_posts()) {
                $products->the_post();
                
                $html .= '<a href="' . esc_url(get_permalink()) . '" class="search-results__item">';
                $html .= '<div class="search-results__image">';
                $html .= getResponsivePostThumbnail(get_the_ID(), 'mobile', ['alt' => get_the_title()]);
                $html .= '</div>';
                $html .= '<div class="search-results__content">';
                $html .= '<h4 class="search-results__item-title">' . esc_html(get_the_title()) . '</h4>';
                $html .= '</div>';
                $html .= '</a>';
            }
            
            $html .= '</div></div>';
            wp_reset_postdata();
        }
    }
    
    // Search Posts
    if (!empty($organized_types['post'])) {
        // First, get total count
        $posts_count_query = new WP_Query([
            'post_type' => 'post',
            'posts_per_page' => -1,
            's' => $search_query,
            'post_status' => 'publish',
            'fields' => 'ids',
        ]);
        $total_posts = $posts_count_query->found_posts;
        wp_reset_postdata();
        
        // Then get limited results
        $posts = new WP_Query([
            'post_type' => 'post',
            'posts_per_page' => 2, // Only show 2 items
            's' => $search_query,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC',
        ]);
        
        if ($posts->have_posts()) {
            $has_results = true;
            $displayed_count = $posts->post_count;
            
            $html .= '<div class="search-results__section">';
            $html .= '<h3 class="search-results__title"><strong>Bài viết liên quan</strong> <span class="search-results__count">(hiển thị ' . $displayed_count . '/' . $total_posts . ' bài viết)</span>:</h3>';
            $html .= '<div class="search-results__list">';
            
            while ($posts->have_posts()) {
                $posts->the_post();
                
                $html .= '<a href="' . esc_url(get_permalink()) . '" class="search-results__item">';
                $html .= '<div class="search-results__image">';
                $html .= getResponsivePostThumbnail(get_the_ID(), 'mobile', ['alt' => get_the_title()]);
                $html .= '</div>';
                $html .= '<div class="search-results__content">';
                $html .= '<h4 class="search-results__item-title">' . esc_html(get_the_title()) . '</h4>';
                $html .= '</div>';
                $html .= '</a>';
            }
            
            $html .= '</div></div>';
            wp_reset_postdata();
        }
    }
    
    // Search Pages
    if (!empty($organized_types['page'])) {
        // First, get total count
        $pages_count_query = new WP_Query([
            'post_type' => 'page',
            'posts_per_page' => -1,
            's' => $search_query,
            'post_status' => 'publish',
            'fields' => 'ids',
        ]);
        $total_pages = $pages_count_query->found_posts;
        wp_reset_postdata();
        
        // Then get limited results
        $pages = new WP_Query([
            'post_type' => 'page',
            'posts_per_page' => 2, // Only show 2 items
            's' => $search_query,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC',
        ]);
        
        if ($pages->have_posts()) {
            $has_results = true;
            $displayed_count = $pages->post_count;
            
            $html .= '<div class="search-results__section">';
            $html .= '<h3 class="search-results__title"><strong>Trang liên quan</strong> <span class="search-results__count">(hiển thị ' . $displayed_count . '/' . $total_pages . ' trang)</span>:</h3>';
            $html .= '<div class="search-results__list">';
            
            while ($pages->have_posts()) {
                $pages->the_post();
                
                $html .= '<a href="' . esc_url(get_permalink()) . '" class="search-results__item">';
                $html .= '<div class="search-results__image">';
                $html .= getResponsivePostThumbnail(get_the_ID(), 'mobile', ['alt' => get_the_title()]);
                $html .= '</div>';
                $html .= '<div class="search-results__content">';
                $html .= '<h4 class="search-results__item-title">' . esc_html(get_the_title()) . '</h4>';
                $html .= '</div>';
                $html .= '</a>';
            }
            
            $html .= '</div></div>';
            wp_reset_postdata();
        }
    }
    
    // Search Other Custom Post Types
    if (!empty($organized_types['other'])) {
        foreach ($organized_types['other'] as $custom_type) {
            // First, get total count
            $custom_count_query = new WP_Query([
                'post_type' => $custom_type,
                'posts_per_page' => -1,
                's' => $search_query,
                'post_status' => 'publish',
                'fields' => 'ids',
            ]);
            $total_custom = $custom_count_query->found_posts;
            wp_reset_postdata();
            
            // Then get limited results
            $custom_posts = new WP_Query([
                'post_type' => $custom_type,
                'posts_per_page' => 2, // Only show 2 items
                's' => $search_query,
                'post_status' => 'publish',
                'orderby' => 'date',
                'order' => 'DESC',
            ]);
            
            if ($custom_posts->have_posts()) {
                $has_results = true;
                $post_type_obj = get_post_type_object($custom_type);
                $type_label = $post_type_obj->labels->name;
                $displayed_count = $custom_posts->post_count;
                
                $html .= '<div class="search-results__section">';
                $html .= '<h3 class="search-results__title"><strong>' . esc_html($type_label) . ' liên quan</strong> <span class="search-results__count">(hiển thị ' . $displayed_count . '/' . $total_custom . ')</span>:</h3>';
                $html .= '<div class="search-results__list">';
                
                while ($custom_posts->have_posts()) {
                    $custom_posts->the_post();
                    
                    $html .= '<a href="' . esc_url(get_permalink()) . '" class="search-results__item">';
                    $html .= '<div class="search-results__image">';
                    $html .= getResponsivePostThumbnail(get_the_ID(), 'mobile', ['alt' => get_the_title()]);
                    $html .= '</div>';
                    $html .= '<div class="search-results__content">';
                    $html .= '<h4 class="search-results__item-title">' . esc_html(get_the_title()) . '</h4>';
                    $html .= '</div>';
                    $html .= '</a>';
                }
                
                $html .= '</div></div>';
                wp_reset_postdata();
            }
        }
    }
    
    // No results found
    if (!$has_results) {
        $html = '<div class="search-results__empty">';
        $html .= '<p>Không tìm thấy kết quả nào cho "<strong>' . esc_html($search_query) . '</strong>"</p>';
        $html .= '</div>';
    }
    
    // Remove search filter after use
    remove_filter('posts_search', 'lacadev_improve_search_relevance', 10);
    
    // Return HTML
    echo $html;
    wp_die();
}

// =============================================================================
// AJAX HANDLERS - CUSTOM SORT, THUMBNAIL, CONTACT FORM, LOAD PAGE
// =============================================================================

if (!defined('ABSPATH')) {
    exit;
}

// -----------------------------------------------------------------------------
// AJAX: Update Custom Sort Order
// -----------------------------------------------------------------------------
/**
 * Cập nhật thứ tự sắp xếp (menu_order) cho các post qua Ajax.
 *
 * @action wp_ajax_update_custom_sort_order
 */
add_action('wp_ajax_update_custom_sort_order', 'updateCustomSortOrder');
function updateCustomSortOrder() {
    // Kiểm tra nonce để bảo vệ CSRF
    check_ajax_referer('update_custom_sort_order', 'nonce');
    
    // Kiểm tra tham số đầu vào
    if (empty($_POST['post_ids']) || empty($_POST['current_page'])) {
        wp_send_json_error(['message' => 'Missing parameters.']);
    }

    $postIds = array_map('absint', $_POST['post_ids']);
    $currentPage = absint($_POST['current_page']);
    $order = (($currentPage - 1) * count($postIds)) + 1;

    // Cập nhật menu_order cho từng post
    foreach ($postIds as $postId) {
        wp_update_post([
            'ID'         => $postId,
            'menu_order' => $order,
        ]);
        $order++;
    }

    wp_send_json_success();
}

// -----------------------------------------------------------------------------
// AJAX: Update Post Thumbnail ID
// -----------------------------------------------------------------------------
/**
 * Cập nhật thumbnail (ảnh đại diện) cho post qua Ajax.
 *
 * @action wp_ajax_nopriv_update_post_thumbnail_id
 * @action wp_ajax_update_post_thumbnail_id
 */
add_action('wp_ajax_nopriv_update_post_thumbnail_id', 'updatePostThumbnailId');
add_action('wp_ajax_update_post_thumbnail_id', 'updatePostThumbnailId');

function updatePostThumbnailId() {
    // Kiểm tra nonce để bảo vệ CSRF
    check_ajax_referer('update_post_thumbnail', 'nonce');
    
    // Kiểm tra các tham số post_id và attachment_id
    if (empty($_POST['post_id']) || empty($_POST['attachment_id'])) {
        wp_send_json_error(['message' => 'Missing parameters.']);
    }

    $postId = absint($_POST['post_id']);
    $attachmentId = absint($_POST['attachment_id']);

    // Cập nhật _thumbnail_id bằng hàm update_post_meta
    if (update_post_meta($postId, '_thumbnail_id', $attachmentId)) {
        wp_send_json_success(['message' => 'Thumbnail updated.']);
    } else {
        wp_send_json_error(['message' => 'Failed to update thumbnail.']);
    }
}

// -----------------------------------------------------------------------------
// AJAX: Remove Post Thumbnail
// -----------------------------------------------------------------------------
/**
 * Xóa thumbnail (ảnh đại diện) cho post qua Ajax.
 *
 * @action wp_ajax_nopriv_remove_post_thumbnail
 * @action wp_ajax_remove_post_thumbnail
 */
add_action('wp_ajax_nopriv_remove_post_thumbnail', 'removePostThumbnail');
add_action('wp_ajax_remove_post_thumbnail', 'removePostThumbnail');

function removePostThumbnail() {
    // Kiểm tra nonce để bảo vệ CSRF
    check_ajax_referer('update_post_thumbnail', 'nonce');
    
    // Kiểm tra tham số post_id
    if (empty($_POST['post_id'])) {
        wp_send_json_error(['message' => 'Missing post ID.']);
    }

    $postId = absint($_POST['post_id']);

    // Xóa thumbnail bằng hàm delete_post_thumbnail
    if (delete_post_thumbnail($postId)) {
        wp_send_json_success(['message' => 'Thumbnail removed.']);
    } else {
        wp_send_json_error(['message' => 'Failed to remove thumbnail.']);
    }
}

// -----------------------------------------------------------------------------
// AJAX: Gửi form liên hệ (Contact Form)
// -----------------------------------------------------------------------------
/**
 * Xử lý gửi form liên hệ qua Ajax, gửi email đến quản trị viên.
 *
 * @action wp_ajax_nopriv_send_contact_form
 * @action wp_ajax_send_contact_form
 */
add_action('wp_ajax_nopriv_send_contact_form', 'sendContactForm');
add_action('wp_ajax_send_contact_form', 'sendContactForm');

function sendContactForm() {
    // Bắt đầu output buffering để tránh lỗi JSON
    ob_start();
    
    // Kiểm tra nonce để bảo mật
    if (!check_ajax_referer('send_contact_form', '_token', false)) {
        ob_end_clean();
        wp_send_json_error(['message' => __('Token mistake.')]);
    }

    // Kiểm tra các trường bắt buộc
    if (empty($_POST['first_name']) || empty($_POST['last_name']) || empty($_POST['email']) || empty($_POST['phone_number']) || empty($_POST['message'])) {
        wp_send_json_error(['message' => __('Please fill in all required fields.', 'mms')]);
    }

    // Lấy thông tin từ form
    $first_name = sanitize_text_field($_POST['first_name']);
    $last_name = sanitize_text_field($_POST['last_name']);
    $email = sanitize_email($_POST['email']);
    $phone_number = sanitize_text_field($_POST['phone_number']);
    $message = sanitize_textarea_field($_POST['message']);

    // Lấy thông tin blog
    $blogName = get_bloginfo('name');
    $blogUrl = get_bloginfo('url');

    // Nội dung email
    $html = sprintf(
        '<p>Send from: %s %s (%s)</p><p>Contact phone number: %s</p><p>Contact message:</p><p>%s</p>',
        esc_html($first_name),
        esc_html($last_name),
        esc_html($email),
        esc_html($phone_number),
        esc_html($message)
    );

    // Thiết lập header
    $headers = [
        'Content-Type: text/html; charset=UTF-8',
        'Reply-To: ' . esc_html($first_name . ' ' . $last_name) . ' <' . sanitize_email($email) . '>',
    ];

    // Gửi email đến quản trị viên
    $success = wp_mail(get_option('admin_email'), $blogName . ': New Contact Form Submission', $html, $headers);

    // Kiểm tra kết quả gửi email và phản hồi JSON
    if ($success) {
        ob_end_clean();
        wp_send_json_success(['message' => __('Your request has been successfully submitted.', 'mms')]);
    } else {
        // Ghi lại log nếu gửi email thất bại
        error_log('Email failed to send.');
        ob_end_clean();
        wp_send_json_error(['message' => __('An error occurred. Please try again later.', 'mms')]);
    }
}

// -----------------------------------------------------------------------------
// AJAX: Load Page Content
// -----------------------------------------------------------------------------
/**
 * Tải nội dung trang qua Ajax (dùng cho các yêu cầu động).
 *
 * @action wp_ajax_nopriv_get_page
 * @action wp_ajax_get_page
 */
add_action('wp_ajax_nopriv_get_page', 'ajaxGetPage');
add_action('wp_ajax_get_page', 'ajaxGetPage');

function ajaxGetPage() {
    ob_start();
    get_template_part('page');
    $content = ob_get_clean();
    wp_send_json_success($content);
}

// -----------------------------------------------------------------------------
// AJAX: Load More Search Results
// -----------------------------------------------------------------------------
/**
 * Load more search results for specific post type
 *
 * @action wp_ajax_nopriv_load_more_search
 * @action wp_ajax_load_more_search
 */
add_action('wp_ajax_nopriv_load_more_search', 'lacadev_load_more_search');
add_action('wp_ajax_load_more_search', 'lacadev_load_more_search');

function lacadev_load_more_search() {
    // Security check
    check_ajax_referer('theme_search_nonce', 'nonce');
    
    // Get parameters
    $post_type = isset($_POST['post_type']) ? sanitize_text_field($_POST['post_type']) : '';
    $search_query = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
    $paged = isset($_POST['paged']) ? absint($_POST['paged']) : 1;
    
    if (empty($post_type) || empty($search_query)) {
        wp_send_json_error(['message' => 'Missing parameters']);
        return;
    }
    
    // Add search filter (title-only, accent-insensitive)
    add_filter('posts_search', 'lacadev_improve_search_relevance', 10, 2);
    
    // Query posts
    $query = new WP_Query([
        'post_type' => $post_type,
        'posts_per_page' => 8,
        's' => $search_query,
        'post_status' => 'publish',
        'paged' => $paged,
    ]);
    
    // Remove search filter
    remove_filter('posts_search', 'lacadev_improve_search_relevance', 10);
    
    if (!$query->have_posts()) {
        wp_send_json_error(['message' => 'No more posts']);
        return;
    }
    
    // Map post_type to template part
    // Pages use 'post' template since loop-page.php doesn't exist
    $template_slug = $post_type;
    if ($post_type === 'page') {
        $template_slug = 'post';
    }
    
    // Generate HTML
    ob_start();
    while ($query->have_posts()) {
        $query->the_post();
        get_template_part('template-parts/loop', $template_slug);
    }
    wp_reset_postdata();
    $html = ob_get_clean();
    
    // Return response
    wp_send_json_success([
        'html' => $html,
        'has_more' => $paged < $query->max_num_pages,
        'next_page' => $paged + 1,
        'max_pages' => $query->max_num_pages,
    ]);
}
