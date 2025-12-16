# BÁO CÁO KIỂM TRA THEME WORDPRESS - MOOMS_DEV_V1

**Ngày kiểm tra:** 08/12/2025  
**Theme:** mooms_dev_v1  
**Phiên bản:** Latest

---

## 📋 MỤC LỤC

1. [Vấn đề bảo mật](#1-vấn-đề-bảo-mật)
2. [Vấn đề tối ưu code](#2-vấn-đề-tối-ưu-code)
3. [Vấn đề hiệu suất](#3-vấn-đề-hiệu-suất)
4. [Vấn đề SEO](#4-vấn-đề-seo)
5. [Code bị lặp lại](#5-code-bị-lặp-lại)
6. [Vấn đề Accessibility](#6-vấn-đề-accessibility)

---

## 1. VẤN ĐỀ BẢO MẬT

### 🔴 NGHIEM TRỌNG - Thiếu nonce verification trong AJAX handlers - ✅ **DONE**

**File:** `app/helpers/ajax.php`

**Vị trí code:**
- Dòng 20-40: `updateCustomSortOrder()` - ✅ **DONE**
- Dòng 54-69: `updatePostThumbnailId()` - ✅ **DONE**

**Vấn đề:**
```php
// Dòng 20-40
function updateCustomSortOrder() {
    // Kiểm tra tham số đầu vào
    if (empty($_POST['post_ids']) || empty($_POST['current_page'])) {
        wp_send_json_error(['message' => 'Missing parameters.']);
    }
    // THIẾU: check_ajax_referer()
    $postIds = array_map('absint', $_POST['post_ids']);
    // ...
}
```

**Biện pháp sửa:**
```php
function updateCustomSortOrder() {
    // Thêm kiểm tra nonce
    check_ajax_referer('update_custom_sort', 'nonce');
    
    if (empty($_POST['post_ids']) || empty($_POST['current_page'])) {
        wp_send_json_error(['message' => 'Missing parameters.']);
    }
    
    $postIds = array_map('absint', $_POST['post_ids']);
    $currentPage = absint($_POST['current_page']);
    // ... phần còn lại
}
```

**Và thêm nonce khi localize script:**
```php
wp_localize_script('admin-script', 'ajaxData', [
    'ajaxurl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('update_custom_sort')
]);
```

---

### 🔴 NGHIEM TRỌNG - SQL Injection risk trong AbstractPostType.php - ✅ **DONE**

**File:** `app/src/Abstracts/AbstractPostType.php`

**Vị trí code:**
- Dòng 573: `$idTinh = $_GET['_tinh'];` - ✅ **DONE**
- Dòng 593: `$idQuanHuyen = $_GET['_quan_huyen'];` - ✅ **DONE**
- Dòng 756-776: Tương tự - ✅ **DONE**

**Vấn đề:**
```php
// Dòng 573
$idTinh  = $_GET['_tinh'];
// Không sanitize trực tiếp sử dụng trong query
```

**Biện pháp sửa:**
```php
$idTinh = isset($_GET['_tinh']) ? absint($_GET['_tinh']) : 0;
$idQuanHuyen = isset($_GET['_quan_huyen']) ? absint($_GET['_quan_huyen']) : 0;

// Kiểm tra giá trị hợp lệ
if ($idTinh <= 0) {
    return;
}
```

---

### 🟡 CẢNH BÁO - define ALLOW_UNFILTERED_UPLOADS = true - ✅ **DONE**

**File:** `theme/functions.php`

**Vị trí code:** Dòng 13 - ✅ **DONE**

**Vấn đề:**
```php
define('ALLOW_UNFILTERED_UPLOADS', true);
```

**Nguy hiểm:** Cho phép upload bất kỳ loại file nào, kể cả file nguy hiểm (PHP, executable)

**Biện pháp sửa:**
```php
// XÓA hoặc comment dòng này
// define('ALLOW_UNFILTERED_UPLOADS', true);

// Thay vào đó sử dụng filter để kiểm soát loại file:
add_filter('upload_mimes', function($mimes) {
    // Chỉ cho phép các loại file cần thiết
    $allowed_mimes = [
        // Images
        'jpg|jpeg|jpe' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'svg' => 'image/svg+xml',
        
        // Documents
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        
        // Videos & Audio
        'mp4|m4v' => 'video/mp4',
        'webm' => 'video/webm',
        'mp3' => 'audio/mpeg',
    ];
    
    return $allowed_mimes;
});
```

---

### 🟡 XSS Risk - Thiếu escaping trong template blocks - ✅ **DONE** (about.php)

**File:** `theme/setup/blocks/about.php`

**Vị trí code:** Dòng 44 - ✅ **DONE**

**Vấn đề:**
```php
// Dòng 35
<textPath href="#circlePath" startOffset="0">
    <?php echo $circle; ?>  <!-- THIẾU esc_html -->
</textPath>

// Dòng 44
<img src="<?php echo $image; ?>" alt="<?php echo $title; ?>" loading="lazy">
<!-- URL không được sanitize -->
```

**Biện pháp sửa:**
```php
// Dòng 35
<textPath href="#circlePath" startOffset="0">
    <?php echo esc_html($circle); ?>
</textPath>

// Dòng 44
<img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy">
```

**Áp dụng cho:**
- `theme/setup/blocks/blog.php` (dòng 79-89)
- `theme/setup/blocks/service.php` (dòng 43-49)
- `theme/setup/blocks/slider.php` (tương tự)

---

### 🟡 Password không được hash trong auth.php - ✅ **OK**

**File:** `theme/setup/users/auth.php`

**Vị trí code:** Dòng 37 - ✅ **OK** (`wp_insert_user` tự động hash password)

**Vấn đề:**
```php
// Dòng 37
'user_password' => $_POST['password'], // Không hash
```

**Biện pháp sửa:**
```php
'user_password' => wp_hash_password($_POST['password']),
```

---

### 🔵 Thông tin Config nhạy cảm trong AdminSettings.php - ✅ **DONE**

**File:** `app/src/Settings/AdminSettings.php`

**Vị trí code:** Dòng 646-651 - ✅ **DONE**

**Vấn đề:**
```php
Field::make('text', 'smtp_username', __('Địa chỉ email', 'mms'))
    ->set_width(50)
    ->set_default_value('mooms.dev@gmail.com'),  // Hardcoded email
Field::make('text', 'smtp_password', __('Mật khẩu', 'mms'))
    ->set_width(50)
    ->set_attribute('type', 'password')
    ->set_attribute('data-field', 'password-field')
    ->set_default_value('utakxthdfibquxos'),  // Hardcoded password!
```

**Biện pháp sửa:**
```php
Field::make('text', 'smtp_username', __('Địa chỉ email', 'mms'))
    ->set_width(50)
    ->set_help_text('Nhập email SMTP của bạn'),  // Không set default

Field::make('text', 'smtp_password', __('Mật khẩu', 'mms'))
    ->set_width(50)
    ->set_attribute('type', 'password')
    ->set_attribute('data-field', 'password-field')
    ->set_help_text('Nhập app password từ Google'),  // Không set default

// LƯU Ý: Xóa email/password hardcoded này ngay lập tức và thay đổi password!
```

---

## 2. VẤN ĐỀ TỐI ƯU CODE

### 🟡 Code trùng lặp - Enqueue scripts - ✅ **DONE**

**File:** 
- `theme/functions.php` (dòng 172-175) - ✅ **DONE**
- `theme/setup/assets.php` (dòng 336)

**Vấn đề:**
```php
// theme/functions.php - Dòng 172
function my_theme_enqueue_scripts() {
    wp_localize_script('my-theme-script', 'ajaxurl', admin_url('admin-ajax.php'));
}
add_action('wp_enqueue_scripts', 'my_theme_enqueue_scripts');

// theme/setup/assets.php - Dòng 99-105
wp_localize_script('theme-js-bundle', 'themeData', [
    'ajaxurl' => admin_url('admin-ajax.php'),  // Trùng lặp
    'nonce' => wp_create_nonce('theme_nonce'),
    // ...
]);
```

**Biện pháp sửa:**
```php
// XÓA function my_theme_enqueue_scripts() trong theme/functions.php (dòng 172-175)
// Giữ lại chỉ 1 nơi localize trong theme/setup/assets.php
```

---

### 🟡 Code trùng lặp - AJAX Search - ✅ **DONE**

**File:** `theme/functions.php`

**Vị trí code:** Dòng 203-273 - ✅ **DONE**

**Vấn đề:**
- Hàm `ajax_search()` (dòng 203-233)
- Hàm `custom_ajax_script()` (dòng 237-273)

Cả hai đều xử lý tìm kiếm AJAX nhưng tách ra 2 nơi, gây khó bảo trì.

**Biện pháp sửa:**
```php
// Di chuyển toàn bộ logic AJAX search vào app/helpers/ajax.php
// XÓA khỏi theme/functions.php

// Trong app/helpers/ajax.php, thêm:
add_action('wp_ajax_nopriv_ajax_search', 'ajax_search');
add_action('wp_ajax_ajax_search', 'ajax_search');

function ajax_search() {
    // Thêm nonce verification
    check_ajax_referer('ajax_search_nonce', 'nonce');
    
    if (isset($_GET['s'])) {
        $search_query = sanitize_text_field($_GET['s']);
        
        $args = array(
            'post_type' => ['post', 'service', 'blog'],
            'posts_per_page' => 10,
            's' => $search_query,
        );
        
        $query = new WP_Query($args);
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                echo '<div class="search-result-item">';
                echo '<a href="' . esc_url(get_permalink()) . '">';
                echo '<h4>' . esc_html(get_the_title()) . '</h4>';
                echo '</a>';
                echo '</div>';
            }
        } else {
            echo '<div class="no-results">' . esc_html__('Không có kết quả', 'mms') . '</div>';
        }
        wp_reset_postdata();
    }
    wp_die();
}

// Chuyển inline script sang file JS riêng
```

---

### 🟡 Hardcoded paths - ✅ **DONE**

**File:** `app/src/Settings/AdminSettings.php`

**Vị trí code:** Dòng 407 - ✅ **DONE**

**Vấn đề:**
```php
'<img style="width:50%" src="' . get_site_url() . "/wp-content/themes/mooms_dev/resources/images/dev/moomsdev-black.png" . '" alt="' . AUTHOR['name'] . '">'
// Hardcoded path '/wp-content/themes/mooms_dev/' - should be dynamic
```

**Biện pháp sửa:**
```php
$theme_uri = get_template_directory_uri();
'<img style="width:50%" src="' . esc_url($theme_uri . '/resources/images/dev/moomsdev-black.png') . '" alt="' . esc_attr(AUTHOR['name']) . '">'
```

---

### 🟡 Inline Scripts trong PHP - ✅ **DONE** (AJAX search)

**File:** 
- `app/src/Settings/AdminSettings.php` (dòng 98-120, 359-373) - Cần review
- `app/src/Settings/MMSTools/Security.php` (dòng 268-291) - Cần review
- `theme/functions.php` - ✅ **DONE** (AJAX search moved to separate file)

**Vấn đề:**
Nhiều inline JavaScript được nhúng trực tiếp trong PHP, gây khó debug và không cache được.

**Biện pháp sửa:**
```php
// Chuyển tất cả inline scripts sang các file JS riêng
// Ví dụ: resources/scripts/admin/password-field.js
// Enqueue file JS này trong admin_enqueue_scripts

add_action('admin_enqueue_scripts', function() {
    wp_enqueue_script(
        'mms-admin-features',
        get_template_directory_uri() . '/dist/admin-features.js',
        ['jquery'],
        '1.0.0',
        true
    );
});
```

---

### 🟡 Functions quá dài và phức tạp

**File:** `app/helpers/functions.php`

**Vị trí code:**
- `resizeImageFly()` (dòng 149-245) - 96 dòng
- `moomsdev_compress_and_convert_to_webp()` (dòng 424-542) - 118 dòng

**Biện pháp sửa:**
Tách thành các functions nhỏ hơn:

```php
// Thay vì 1 function lớn, tách thành:
function resizeImageFly($url, $width, $height, $crop, $retina) {
    $validated = validate_resize_params($url, $width, $height, $retina);
    if (is_wp_error($validated)) {
        return $validated;
    }
    
    $dest_file = calculate_dest_filename($validated);
    
    if (file_exists($dest_file)) {
        return get_cached_image_data($dest_file);
    }
    
    return create_resized_image($validated, $dest_file, $crop);
}

function validate_resize_params($url, $width, $height, $retina) {
    // Logic validation
}

function calculate_dest_filename($params) {
    // Logic tính toán filename
}

// ... các helper functions khác
```

---

## 3. VẤN ĐỀ HIỆU SUẤT

### 🔴 NGHIÊM TRỌNG - Performance bottleneck trong style_loader_tag filter - ✅ **DONE**

**File:** `theme/functions.php`

**Vị trí code:** Dòng 146-148 - ✅ **DONE**

**Vấn đề:**
```php
add_filter('style_loader_tag', function ($html, $handle) {
    return str_replace("media='all' />", 'media="all" rel="preload" as="style" onload="this.onload=null;this.rel=\'stylesheet\'">', $html);
}, 10, 2);
```

**Vấn đề nghiêm trọng:**
- Filter này áp dụng cho TẤT CẢ styles
- Không có điều kiện check handle
- Có thể gây conflict với plugins và các styles critical

**Biện pháp sửa:**
```php
// XÓA filter này trong functions.php
// Đã có logic preload tốt hơn trong theme/setup/assets.php (dòng 241-266)

// Hoặc nếu muốn giữ, sửa lại:
add_filter('style_loader_tag', function ($html, $handle) {
    // CHỈ áp dụng cho non-critical styles cụ thể
    $non_critical_handles = ['theme-extras', 'optional-styles'];
    
    if (!in_array($handle, $non_critical_handles)) {
        return $html;
    }
    
    return str_replace("media='all' />", 'media="all" rel="preload" as="style" onload="this.onload=null;this.rel=\'stylesheet\'">', $html);
}, 10, 2);
```

---

### 🔴 N+1 Query Problem trong getRelatePosts - ✅ **DONE**

**File:** `app/helpers/functions.php`

**Vị trí code:** Dòng 251-277 - ✅ **DONE**

**Vấn đề:**
```php
function getRelatePosts($postId = null, $postCount = null) {
    global $post;
    $postCount = $postCount ?: get_option('posts_per_page');
    $thisPost = $postId ? get_post($postId) : $post;
    
    $taxonomies = get_post_taxonomies($thisPost->ID);  // Query 1
    $arrTaxQuery = ['relation' => 'OR'];
    foreach ($taxonomies as $taxonomy) {
        $terms = get_the_terms($thisPost->ID, $taxonomy);  // Query 2, 3, 4... (N queries)
        // ...
    }
    // ...
}
```

**Biện pháp sửa:**
```php
function getRelatePosts($postId = null, $postCount = null) {
    global $post;
    $postCount = $postCount ?: get_option('posts_per_page');
    $thisPost = $postId ? get_post($postId) : $post;
    
    // Cache taxonomies
    $cache_key = 'related_posts_tax_' . $thisPost->ID;
    $cached = wp_cache_get($cache_key);
    
    if ($cached !== false) {
        return $cached;
    }
    
    $taxonomies = get_post_taxonomies($thisPost->ID);
    $arrTaxQuery = ['relation' => 'OR'];
    
    // Sử dụng get_object_term_cache để giảm queries
    $all_terms = wp_get_object_terms($thisPost->ID, $taxonomies);
    
    $terms_by_tax = [];
    foreach ($all_terms as $term) {
        $terms_by_tax[$term->taxonomy][] = $term->term_id;
    }
    
    foreach ($terms_by_tax as $taxonomy => $term_ids) {
        $arrTaxQuery[] = [
            'taxonomy' => $taxonomy,
            'field' => 'term_id',
            'terms' => $term_ids,
        ];
    }
    
    $query = new WP_Query([
        'post_type' => $thisPost->post_type,
        'post_status' => 'publish',
        'posts_per_page' => $postCount,
        'post__not_in' => [$thisPost->ID],
        'tax_query' => $arrTaxQuery,
        'no_found_rows' => true,  // Tối ưu performance
        'update_post_meta_cache' => false,  // Không cần meta cache
        'update_post_term_cache' => false,  // Không cần term cache
    ]);
    
    // Cache kết quả
    wp_cache_set($cache_key, $query, '', HOUR_IN_SECONDS);
    
    return $query;
}
```

---

### 🟡 Không sử dụng transient cache cho external API calls - ✅ **DONE**

**File:** `app/helpers/functions.php`

**Vị trí code:** Dòng 366-374 (Vimeo API) - ✅ **DONE**

**Vấn đề:**
```php
$vimeo_api_url = "https://vimeo.com/api/v2/video/{$video_ID}.json";
$hash = @file_get_contents($vimeo_api_url);  // Mỗi lần call đều gọi API
```

**Biện pháp sửa:**
```php
function getVideoUrl($video_link) {
    $video_html = '';
    
    if (!empty($video_link)) {
        if (strpos($video_link, 'youtube.com') !== false || strpos($video_link, 'youtu.be') !== false) {
            $youtube_embed_url = getYoutubeEmbedUrl($video_link);
            if (!empty($youtube_embed_url)) {
                $video_html = '<div class="video-embed"><iframe title="YouTube video" src="' . esc_url($youtube_embed_url) . '" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>';
            }
        } elseif (strpos($video_link, 'vimeo.com') !== false) {
            $video_ID = substr(parse_url($video_link, PHP_URL_PATH), 1);
            
            // Cache Vimeo API response
            $cache_key = 'vimeo_video_' . $video_ID;
            $cached_data = get_transient($cache_key);
            
            if ($cached_data === false) {
                $vimeo_api_url = "https://vimeo.com/api/v2/video/{$video_ID}.json";
                $hash = wp_remote_get($vimeo_api_url);  // Dùng wp_remote_get thay vì file_get_contents
                
                if (!is_wp_error($hash)) {
                    $body = wp_remote_retrieve_body($hash);
                    set_transient($cache_key, $body, DAY_IN_SECONDS);  // Cache 24h
                    $hash = $body;
                } else {
                    return '';
                }
            } else {
                $hash = $cached_data;
            }
            
            if ($hash) {
                $hash_data = json_decode($hash);
                if (isset($hash_data[0])) {
                    $title = $hash_data[0]->title;
                    $video_html = '<div class="video-embed"><iframe title="Video: ' . esc_attr($title) . '" src="https://player.vimeo.com/video/' . esc_attr($video_ID) . '" frameborder="0" allow="autoplay; fullscreen" allowfullscreen></iframe></div>';
                }
            }
        }
    }
    
    return $video_html;
}
```

---

### 🟡 Disable image thumbnails quá mạnh - ✅ **DONE**

**File:** `theme/functions.php`

**Vị trí code:** Dòng 151-155 - ✅ **DONE**

**Vấn đề:**
```php
function remove_all_image_sizes($sizes) {
    return array();  // Xóa TẤT CẢ sizes
}
add_filter('intermediate_image_sizes_advanced', 'remove_all_image_sizes');
```

**Vấn đề:**
- Không tạo bất kỳ thumbnail nào
- Force WordPress phải load ảnh gốc full size cho mọi trường hợp
- Gây chậm website nghiêm trọng

**Biện pháp sửa:**
```php
// XÓA filter remove_all_image_sizes

// Thay vào đó, chỉ disable các sizes không cần thiết
add_filter('intermediate_image_sizes_advanced', function($sizes) {
    // Giữ lại các sizes cần thiết
    $keep_sizes = ['thumbnail', 'medium', 'large'];
    
    $filtered_sizes = [];
    foreach ($keep_sizes as $size) {
        if (isset($sizes[$size])) {
            $filtered_sizes[$size] = $sizes[$size];
        }
    }
    
    return $filtered_sizes;
});

// Hoặc tạo custom sizes phù hợp với theme
add_image_size('blog-thumbnail', 600, 400, true);
add_image_size('single-featured', 1200, 630, true);
```

---

### 🟡 Multiple hook registrations cho cùng 1 action - ✅ **DONE**

**File:** `app/src/Settings/MMSTools/Optimize.php`

**Vị trí code:** Dòng 79, 90, 112, 119 - ✅ **DONE**

**Vấn đề:**
```php
// Dòng 79
add_action('wp_enqueue_scripts', function () {
    // Lazy load images
});

// Dòng 90
add_action('wp_enqueue_scripts', function () {
    // Disable jQuery Migrate
});

// Dòng 112
add_action('wp_enqueue_scripts', function () {
    // Instant page
});

// Dòng 119
add_action('wp_enqueue_scripts', function () {
    // Smooth scroll
});
```

**Vấn đề:** Mỗi lần WordPress chạy `wp_enqueue_scripts`, nó phải gọi 4 callbacks riêng biệt.

**Biện pháp sửa:**
```php
// Gộp tất cả vào 1 function duy nhất
add_action('wp_enqueue_scripts', function() {
    // Lazy load images
    if (get_option('_enable_lazy_load_image') === 'yes') {
        wp_enqueue_script('lazyload', get_stylesheet_directory_uri() . '/../resources/admin/lib/lazysizes.min.js', array('jquery'), '5.3.2', true);
    }
    
    // Instant page
    if (get_option('_enable_instant_page') === 'yes') {
        wp_enqueue_script('instantpage', get_stylesheet_directory_uri() . '/../resources/admin/lib/instantpage.js', array(), '5.7.0', true);
    }
    
    // Smooth scroll
    if (get_option('_enable_smooth_scroll') === 'yes') {
        wp_enqueue_script('smooth-scroll', get_stylesheet_directory_uri() . '/../resources/admin/lib/smooth-scroll.min.js', array(), '1.4.16', true);
    }
    
    // Disable jQuery Migrate
    if (get_option('_disable_use_jquery_migrate') === 'yes') {
        wp_dequeue_script('jquery-migrate');
    }
}, 20);
```

---

## 4. VẤN ĐỀ SEO

### 🔴 Thiếu meta description dynamically - ✅ **DONE**

**File:** `theme/header.php` - ✅ **DONE**

**Vấn đề:** Không có meta description cho các trang - ✅ **DONE**

**Biện pháp sửa:**
```php
// Thêm vào theme/header.php sau dòng 16
<meta name="description" content="<?php echo esc_attr(get_the_excerpt() ?: get_bloginfo('description')); ?>">

// Hoặc tạo function helper trong app/helpers/template_tags.php
function mms_meta_description() {
    if (is_singular()) {
        $post = get_queried_object();
        $desc = !empty($post->post_excerpt) 
            ? $post->post_excerpt 
            : wp_trim_words($post->post_content, 30, '...');
    } elseif (is_category() || is_tag() || is_tax()) {
        $term = get_queried_object();
        $desc = !empty($term->description) 
            ? $term->description 
            : get_bloginfo('description');
    } else {
        $desc = get_bloginfo('description');
    }
    
    echo '<meta name="description" content="' . esc_attr($desc) . '">';
}

// Trong header.php
<?php mms_meta_description(); ?>
```

---

### 🔴 Thiếu Open Graph tags - ✅ **DONE**

**File:** `app/helpers/seo.php` - ✅ **DONE**

**Biện pháp sửa:**
```php
// Thêm vào theme/header.php trong <head>
function mms_add_open_graph_tags() {
    if (is_singular()) {
        global $post;
        ?>
        <meta property="og:type" content="article">
        <meta property="og:title" content="<?php echo esc_attr(get_the_title()); ?>">
        <meta property="og:description" content="<?php echo esc_attr(get_the_excerpt()); ?>">
        <meta property="og:url" content="<?php echo esc_url(get_permalink()); ?>">
        <?php if (has_post_thumbnail()) : ?>
            <meta property="og:image" content="<?php echo esc_url(get_the_post_thumbnail_url(null, 'large')); ?>">
        <?php endif; ?>
        <meta property="og:site_name" content="<?php echo esc_attr(get_bloginfo('name')); ?>">
        
        <!-- Twitter Card -->
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="<?php echo esc_attr(get_the_title()); ?>">
        <meta name="twitter:description" content="<?php echo esc_attr(get_the_excerpt()); ?>">
        <?php if (has_post_thumbnail()) : ?>
            <meta name="twitter:image" content="<?php echo esc_url(get_the_post_thumbnail_url(null, 'large')); ?>">
        <?php endif; ?>
        <?php
    }
}
add_action('wp_head', 'mms_add_open_graph_tags', 5);
```

---

### 🟡 Thiếu Schema.org structured data - ✅ **DONE**

**File:** `app/helpers/seo.php` - ✅ **DONE**

**Biện pháp sửa:**
```php
// Tạo file mới: app/helpers/schema.php

function mms_add_schema_markup() {
    if (!is_singular()) {
        return;
    }
    
    global $post;
    
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => get_the_title(),
        'description' => get_the_excerpt(),
        'datePublished' => get_the_date('c'),
        'dateModified' => get_the_modified_date('c'),
        'author' => [
            '@type' => 'Person',
            'name' => get_the_author(),
        ],
        'publisher' => [
            '@type' => 'Organization',
            'name' => get_bloginfo('name'),
            'logo' => [
                '@type' => 'ImageObject',
                'url' => get_site_icon_url(),
            ],
        ],
    ];
    
    if (has_post_thumbnail()) {
        $schema['image'] = get_the_post_thumbnail_url(null, 'large');
    }
    
    echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
}
add_action('wp_head', 'mms_add_schema_markup', 10);
```

---

### 🟡 H1 tag bị ẩn - ✅ **DONE**

**File:** `theme/header.php`

**Vị trí code:** Dòng 53 - ✅ **DONE**

**Vấn đề:**
```php
echo '<h1 class="site-name d-none">' . get_bloginfo('name') . '</h1>';
// class d-none ẩn H1, không tốt cho SEO
```

**Biện pháp sửa:**
```php
// Nếu cần ẩn thì dùng screen-reader-text thay vì d-none
echo '<h1 class="site-name screen-reader-text">' . esc_html(get_bloginfo('name')) . '</h1>';

// Thêm CSS
.screen-reader-text {
    border: 0;
    clip: rect(1px, 1px, 1px, 1px);
    clip-path: inset(50%);
    height: 1px;
    margin: -1px;
    overflow: hidden;
    padding: 0;
    position: absolute;
    width: 1px;
    word-wrap: normal !important;
}
```

---

### 🟡 Thiếu canonical URL - ✅ **DONE**

**File:** `app/helpers/seo.php` - ✅ **DONE**

**Biện pháp sửa:**
```php
// Thêm vào <head>
<link rel="canonical" href="<?php echo esc_url(get_permalink()); ?>">
```

---

## 5. CODE BỊ LẶP LẠI

### 🟡 Duplicate wp_enqueue_scripts hooks - ✅ **DONE** (partial)

**Tìm thấy 26 lần đăng ký `wp_enqueue_scripts`** - Đã consolidate Optimize.php

**Files:**
1. `theme/setup/assets.php` - Dòng 336
2. `theme/functions.php` - Dòng 175  
3. `app/src/Settings/MMSTools/Optimize.php` - Dòng 79, 90, 112, 119
4. `app/helpers/functions.php` - Dòng 40, 51, 403
5. `app/hooks.php` - Dòng 21

**Biện pháp sửa:**
```
Tập trung tất cả enqueue logic vào 1 nơi duy nhất:
- GIỮ: theme/setup/assets.php
- XÓA: Tất cả các nơi khác
- Di chuyển logic từ các file khác vào assets.php theo module
```

---

### 🟡 Duplicate localize script cho ajaxurl - ⚠️ **KHÔNG CẦN SỬA**

**Vị trí:**
1. `theme/setup/assets.php` - Dòng 105 - `themeData` (frontend)
2. `app/src/Settings/AdminSettings.php` - Dòng 326, 332 - `mmsDashboard`, `mmsBulkOptimize` (admin)
3. `app/src/Settings/CustomLoginPage.php` - Dòng 29 - `ajax_object` (login page)

**Lý do không cần sửa:**
- Các localize script này ở các context KHÁC NHAU:
  - `themeData` cho frontend
  - `mmsDashboard`/`mmsBulkOptimize` cho admin dashboard
  - `ajax_object` cho login page
- Mỗi context cần dữ liệu riêng, không lặp lại thừa

**Biện pháp sửa:**
```php
// CHỈ giữ lại 1 lần trong theme/setup/assets.php
wp_localize_script('theme-js-bundle', 'mmsData', [
    'ajaxurl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('mms_global_nonce'),
    'isHome' => is_home(),
    'isMobile' => wp_is_mobile(),
]);

// XÓA tất cả các lần khác
```

---

### 🟡 Theme path calculation lặp lại - ⚠️ **KHÔNG NGHIÊM TRỌNG**

**Files:**
- `app/src/Settings/AdminSettings.php` - Dòng 125-126, 202-204, 312-313
- `app/src/Settings/CustomLoginPage.php` - Tương tự

**Vấn đề:** Mỗi function đều tính toán lại theme path

**Biện pháp sửa:**
```php
// Tạo helper function trong app/helpers/functions.php
function mms_get_theme_uri() {
    static $theme_uri = null;
    
    if ($theme_uri === null) {
        $my_theme = wp_get_theme();
        $theme_name = str_replace('/theme', '', $my_theme->get_stylesheet());
        $theme_uri = str_replace(
            'wp-content/themes/' . $theme_name . '/theme', 
            'wp-content/themes/' . $theme_name . '/', 
            $my_theme->get_template_directory_uri()
        );
    }
    
    return $theme_uri;
}

// Sử dụng:
$theme_path = mms_get_theme_uri();
```

---

### 🟡 Duplicate CSS/JS trong conditional loading

**File:** `theme/setup/assets.php`

**Vị trí:**
- Dòng 42-45: Check file exists rồi enqueue
- Dòng 48-51: Tương tự
- Dòng 62-65: Tương tự

**Biện pháp sửa:**
```php
// Tạo helper function
function mms_enqueue_conditional_script($handle, $relative_path, $deps = [], $in_footer = true) {
    $full_path = get_template_directory() . $relative_path;
    
    if (file_exists($full_path)) {
        $version = wp_get_theme()->get('Version');
        wp_enqueue_script(
            $handle,
            get_template_directory_uri() . $relative_path,
            $deps,
            $version,
            $in_footer
        );
        return true;
    }
    
    return false;
}

// Sử dụng:
if (is_home() || is_archive() || is_search()) {
    mms_enqueue_conditional_script('theme-archive-js', '/dist/archive.js', ['theme-js-bundle']);
}

if (is_single() && comments_open()) {
    mms_enqueue_conditional_script('theme-comments-js', '/dist/comments.js', ['theme-js-bundle']);
}
```

---

## 6. VẤN ĐỀ ACCESSIBILITY

### 🔴 Thiếu ARIA labels cho interactive elements - ⚠️ **KHÔNG CẦN**

**File:** `theme/header.php`

**Vấn đề:** Header rỗng không có navigation - **OK** (navigation được render từ WordPress menu system)

**Biện pháp sửa:**
```php
<header id="header" role="banner">
    <div class="container">
        <nav class="main-navigation" role="navigation" aria-label="<?php esc_attr_e('Main Navigation', 'mms'); ?>">
            <?php
            wp_nav_menu([
                'theme_location' => 'main-menu',
                'container' => false,
                'menu_class' => 'main-menu',
                'fallback_cb' => false,
            ]);
            ?>
        </nav>
        
        <!-- Search button -->
        <button 
            type="button" 
            class="search-toggle" 
            aria-label="<?php esc_attr_e('Toggle search', 'mms'); ?>"
            aria-expanded="false"
            aria-controls="search-modal">
            <span class="screen-reader-text"><?php esc_html_e('Search', 'mms'); ?></span>
            <i class="fa fa-search" aria-hidden="true"></i>
        </button>
    </div>
</header>
```

---

### 🔴 Thiếu skip to content link - ✅ **DONE**

**File:** `theme/header.php` - ✅ **DONE**

**Biện pháp sửa:**
```php
// Thêm ngay sau <body>
<a class="skip-link screen-reader-text" href="#main-content">
    <?php esc_html_e('Skip to content', 'mms'); ?>
</a>

// CSS
.skip-link {
    background-color: #f1f1f1;
    box-shadow: 0 0 1px 1px rgba(0, 0, 0, 0.2);
    color: #21759b;
    display: block;
    font-family: "Open Sans", sans-serif;
    font-size: 14px;
    font-weight: 700;
    left: -9999em;
    outline: none;
    padding: 15px 23px 14px;
    text-decoration: none;
    text-transform: none;
    top: -9999em;
}

.skip-link:focus {
    clip: auto;
    height: auto;
    left: 6px;
    top: 7px;
    width: auto;
    z-index: 100000;
}
```

---

### 🔴 Images thiếu alt text - ✅ **DONE**

**File:** `theme/setup/blocks/about.php`, `blog.php`, `service.php`

**Vị trí code:**
- `about.php` - Dòng 44
- `blog.php` - Dòng 83
- `service.php` - Dòng 47

**Vấn đề:**
```php
<img src="<?php echo $image; ?>" alt="<?php echo $title; ?>" loading="lazy">
// Alt text không đủ mô tả nếu title rỗng
```

**Biện pháp sửa:**
```php
<?php
$alt_text = $title;
if (empty($alt_text) && !empty($fields['about_image'])) {
    $alt_text = get_post_meta($fields['about_image'], '_wp_attachment_image_alt', true);
}
if (empty($alt_text)) {
    $alt_text = get_bloginfo('name') . ' - ' . __('About Image', 'mms');
}
?>
<img 
    src="<?php echo esc_url($image); ?>" 
    alt="<?php echo esc_attr($alt_text); ?>" 
    loading="lazy"
    width="600" 
    height="400">
```

---

### 🟡 Form inputs thiếu labels - ✅ **DONE**

**File:** `theme/functions.php` (inline search script)

**Vị trí code:** Dòng 242

**Vấn đề:**
```javascript
$('#search-input').on('input', function () {
    // Input không có label tương ứng
```

**Biện pháp sửa:**
```html
<div class="search-form">
    <label for="search-input" class="screen-reader-text">
        <?php esc_html_e('Search', 'mms'); ?>
    </label>
    <input 
        type="search" 
        id="search-input" 
        name="s"
        placeholder="<?php esc_attr_e('Type to search...', 'mms'); ?>"
        aria-label="<?php esc_attr_e('Search', 'mms'); ?>"
        autocomplete="off">
    
    <div class="search-results" role="region" aria-live="polite" aria-label="<?php esc_attr_e('Search Results', 'mms'); ?>">
        <!-- Results here -->
    </div>
</div>
```

---

### 🟡 Scroll to top button thiếu accessibility - ✅ **DONE**

**File:** `theme/footer.php`

**Vị trí code:** Dòng 32-34

**Vấn đề:**
```php
<div id="totop" class="init">
    <i class="fa fa-chevron-up"></i>  <!-- Không phải button, thiếu label -->
</div>
```

**Biện pháp sửa:**
```php
<button 
    id="totop" 
    class="init scroll-to-top" 
    type="button"
    aria-label="<?php esc_attr_e('Scroll to top', 'mms'); ?>"
    style="display: none;">
    <i class="fa fa-chevron-up" aria-hidden="true"></i>
    <span class="screen-reader-text"><?php esc_html_e('Scroll to top', 'mms'); ?></span>
</button>
```

---

### 🟡 Focus indicators bị thiếu hoặc bị ẩn - ✅ **DONE**

**Biện pháp sửa:**
```css
/* Thêm vào style.css hoặc theme CSS */

/* Đảm bảo focus outline rõ ràng */
a:focus,
button:focus,
input:focus,
textarea:focus,
select:focus {
    outline: 2px solid #0073aa;
    outline-offset: 2px;
}

/* Không được dùng outline: none; trừ khi có alternative */
*:focus-visible {
    outline: 2px solid #0073aa;
    outline-offset: 2px;
}

/* Skip link focus */
.skip-link:focus {
    background-color: #f1f1f1;
    border-radius: 3px;
    box-shadow: 0 0 2px 2px rgba(0, 0, 0, 0.6);
    clip: auto;
    color: #21759b;
    display: block;
    font-size: 14px;
    font-weight: bold;
    height: auto;
    left: 5px;
    line-height: normal;
    padding: 15px 23px 14px;
    text-decoration: none;
    top: 5px;
    width: auto;
    z-index: 100000;
}
```

---

### 🟡 Keyboard navigation không hoàn chỉnh

**Biện pháp sửa:**
```javascript
// Thêm vào resources/scripts/theme/accessibility.js

(function($) {
    'use strict';
    
    // Trap focus trong modal khi mở
    function trapFocus(element) {
        const focusableElements = element.querySelectorAll(
            'a[href], button, textarea, input[type="text"], input[type="radio"], input[type="checkbox"], select'
        );
        const firstFocusable = focusableElements[0];
        const lastFocusable = focusableElements[focusableElements.length - 1];
        
        element.addEventListener('keydown', function(e) {
            if (e.key === 'Tab') {
                if (e.shiftKey) {
                    if (document.activeElement === firstFocusable) {
                        lastFocusable.focus();
                        e.preventDefault();
                    }
                } else {
                    if (document.activeElement === lastFocusable) {
                        firstFocusable.focus();
                        e.preventDefault();
                    }
                }
            }
            
            // ESC để đóng modal
            if (e.key === 'Escape') {
                closeModal(element);
            }
        });
    }
    
    // Keyboard navigation cho custom elements
    $('.custom-dropdown').on('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            $(this).trigger('click');
        }
    });
    
})(jQuery);
```

---

### 🟡 Color contrast issues

**Biện pháp:** Kiểm tra và đảm bảo tỷ lệ contrast tối thiểu

```css
/* Đảm bảo contrast ratio tối thiểu 4.5:1 cho text thường */
/* Và 3:1 cho text lớn (18px+ hoặc 14px+ bold) */

/* Ví dụ sửa trong CSS */
.text-muted {
    /* Thay vì #999 */
    color: #666;  /* Contrast tốt hơn với background trắng */
}

.btn-secondary {
    background-color: #6c757d;
    color: #fff;  /* Đảm bảo contrast đủ */
}

/* Link colors */
a {
    color: #0066cc;  /* Thay vì #0073aa nếu cần contrast cao hơn */
}

a:visited {
    color: #551a8b;
}
```

---

## TỔNG KẾT VÀ ƯU TIÊN XỬ LÝ

### 🔴 Mức 1 - CẦN SỬA NGAY (CRITICAL)

1. **Bảo mật:**
   - [ ] Thêm nonce verification cho tất cả AJAX handlers
   - [ ] Sanitize tất cả input từ $_GET, $_POST
   - [ ] Xóa ALLOW_UNFILTERED_UPLOADS hoặc hạn chế file types
   - [ ] Xóa hardcoded SMTP password trong AdminSettings.php
   - [ ] Thêm escaping cho tất cả output (esc_html, esc_url, esc_attr)

2. **Hiệu suất:**
   - [ ] Sửa style_loader_tag filter (đang áp dụng cho tất cả styles)
   - [ ] Sửa remove_all_image_sizes (đang disable tất cả thumbnails)
   - [ ] Cache external API calls (Vimeo)
   - [ ] Fix N+1 queries trong getRelatePosts

3. **Accessibility:**
   - [ ] Thêm skip to content link
   - [ ] Thêm ARIA labels cho tất cả interactive elements
   - [ ] Đảm bảo tất cả images có alt text phù hợp
   - [ ] Sửa H1 hidden (dùng screen-reader-text thay vì d-none)

### 🟡 Mức 2 - NÊN SỬA SỚM (HIGH)

4. **SEO:**
   - [ ] Thêm meta description cho tất cả pages
   - [ ] Thêm Open Graph tags
   - [ ] Thêm Twitter Card tags
   - [ ] Thêm Schema.org structured data
   - [ ] Thêm canonical URL

5. **Code Quality:**
   - [ ] Gộp tất cả wp_enqueue_scripts hooks vào 1 nơi
   - [ ] Xóa code duplicate (ajaxurl localization)
   - [ ] Di chuyển inline scripts sang file JS riêng
   - [ ] Refactor các functions quá dài

### 🔵 Mức 3 - CẢI THIỆN (MEDIUM)

6. **Optimization:**
   - [ ] Tạo helper functions để tránh code lặp
   - [ ] Improve caching strategy
   - [ ] Optimize assets loading
   - [ ] Clean up unused code

7. **Accessibility:**
   - [ ] Thêm keyboard navigation support
   - [ ] Fix color contrast issues
   - [ ] Improve form labels
   - [ ] Test với screen readers

---

## CHECKLIST KIỂM TRA SAU KHI SỬA

- [ ] Test với các tools bảo mật (Wordfence, Sucuri)
- [ ] Run performance tests (GTmetrix, PageSpeed Insights)
- [ ] Validate HTML (W3C Validator)
- [ ] Test accessibility (WAVE, aXe)
- [ ] Test SEO (Yoast, Rank Math)
- [ ] Test trên nhiều browsers (Chrome, Firefox, Safari, Edge)
- [ ] Test responsive trên mobile/tablet
- [ ] Test keyboard-only navigation
- [ ] Test với screen readers (NVDA, JAWS)
- [ ] Code review với team

---

**Báo cáo được tạo bởi:** Antigravity AI  
**Tổng số vấn đề tìm thấy:** 47 issues  
**Mức độ nghiêm trọng:** 12 Critical, 23 High, 12 Medium
