<?php

/**
 * AJAX Search Handler
 */
add_action('wp_ajax_nopriv_ajax_search', 'mms_ajax_search');
add_action('wp_ajax_ajax_search', 'mms_ajax_search');

function mms_ajax_search() {
    // Get search query
    $search_query = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
    
    // Minimum search length
    if (strlen($search_query) < 2) {
        wp_send_json_error(['message' => 'Vui lòng nhập ít nhất 2 ký tự']);
        return;
    }
    
    $html = '';
    $has_results = false;
    $default_img = get_template_directory_uri() . '/resources/images/placeholder.png';
    
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
        $products = new WP_Query([
            'post_type' => 'product',
            'posts_per_page' => 5,
            's' => $search_query,
            'post_status' => 'publish',
        ]);
        
        if ($products->have_posts()) {
            $has_results = true;
            $html .= '<div class="search-results__section">';
            $html .= '<h3 class="search-results__title"><strong>Sản phẩm liên quan:</strong></h3>';
            $html .= '<div class="search-results__list">';
            
            while ($products->have_posts()) {
                $products->the_post();
                $thumbnail = get_the_post_thumbnail_url(get_the_ID(), 'thumbnail');
                
                $html .= '<a href="' . esc_url(get_permalink()) . '" class="search-results__item">';
                $html .= '<div class="search-results__image">';
                $html .= '<img src="' . esc_url($thumbnail ?: $default_img) . '" alt="' . esc_attr(get_the_title()) . '">';
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
        $posts = new WP_Query([
            'post_type' => 'post',
            'posts_per_page' => 5,
            's' => $search_query,
            'post_status' => 'publish',
        ]);
        
        if ($posts->have_posts()) {
            $has_results = true;
            $html .= '<div class="search-results__section">';
            $html .= '<h3 class="search-results__title"><strong>Bài viết liên quan:</strong></h3>';
            $html .= '<div class="search-results__list">';
            
            while ($posts->have_posts()) {
                $posts->the_post();
                $thumbnail = get_the_post_thumbnail_url(get_the_ID(), 'thumbnail');
                
                $html .= '<a href="' . esc_url(get_permalink()) . '" class="search-results__item">';
                $html .= '<div class="search-results__image">';
                $html .= '<img src="' . esc_url($thumbnail ?: $default_img) . '" alt="' . esc_attr(get_the_title()) . '">';
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
        $pages = new WP_Query([
            'post_type' => 'page',
            'posts_per_page' => 5,
            's' => $search_query,
            'post_status' => 'publish',
        ]);
        
        if ($pages->have_posts()) {
            $has_results = true;
            $html .= '<div class="search-results__section">';
            $html .= '<h3 class="search-results__title"><strong>Trang liên quan:</strong></h3>';
            $html .= '<div class="search-results__list">';
            
            while ($pages->have_posts()) {
                $pages->the_post();
                $thumbnail = get_the_post_thumbnail_url(get_the_ID(), 'thumbnail');
                
                $html .= '<a href="' . esc_url(get_permalink()) . '" class="search-results__item">';
                $html .= '<div class="search-results__image">';
                $html .= '<img src="' . esc_url($thumbnail ?: $default_img) . '" alt="' . esc_attr(get_the_title()) . '">';
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
            $custom_posts = new WP_Query([
                'post_type' => $custom_type,
                'posts_per_page' => 3,
                's' => $search_query,
                'post_status' => 'publish',
            ]);
            
            if ($custom_posts->have_posts()) {
                $has_results = true;
                $post_type_obj = get_post_type_object($custom_type);
                $type_label = $post_type_obj->labels->name;
                
                $html .= '<div class="search-results__section">';
                $html .= '<h3 class="search-results__title"><strong>' . esc_html($type_label) . ' liên quan:</strong></h3>';
                $html .= '<div class="search-results__list">';
                
                while ($custom_posts->have_posts()) {
                    $custom_posts->the_post();
                    $thumbnail = get_the_post_thumbnail_url(get_the_ID(), 'thumbnail');
                    
                    $html .= '<a href="' . esc_url(get_permalink()) . '" class="search-results__item">';
                    $html .= '<div class="search-results__image">';
                    $html .= '<img src="' . esc_url($thumbnail ?: $default_img) . '" alt="' . esc_attr(get_the_title()) . '">';
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
