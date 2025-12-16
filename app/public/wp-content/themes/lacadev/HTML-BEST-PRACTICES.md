# 📘 HTML BEST PRACTICES - CHUẨN SEO, ACCESSIBILITY, SECURITY, PERFORMANCE

> **Mục đích:** Hướng dẫn chi tiết cách viết HTML chuẩn 100% cho WordPress theme  
> **Áp dụng:** SEO, Accessibility (WCAG 2.1 AA), Security, Performance  
> **Version:** 1.0 - December 2025

---

## 📋 MỤC LỤC

1. [Document Structure](#document-structure)
2. [Metadata & SEO](#metadata--seo)
3. [Navigation](#navigation)
4. [Forms & Inputs](#forms--inputs)
5. [Images & Media](#images--media)
6. [Links & Buttons](#links--buttons)
7. [Text Content](#text-content)
8. [Interactive Elements](#interactive-elements)
9. [Lists](#lists)
10. [Tables](#tables)
11. [Semantic HTML5](#semantic-html5)

---

## 1. DOCUMENT STRUCTURE

### `<!DOCTYPE html>`

```html
<!DOCTYPE html>
```

**✅ Tại sao:**
- ✅ Bắt buộc cho HTML5
- ✅ Đảm bảo browser render ở standards mode
- ✅ SEO: Google yêu cầu DOCTYPE hợp lệ

**❌ Không làm:**
```html
<!-- WRONG: Old HTML4 DOCTYPE -->
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN">
```

---

### `<html>` - Root Element

```php
<html <?php language_attributes(); ?>>
```

**✅ Tại sao:**
- ✅ `lang="vi"` - SEO: Google biết ngôn ngữ content
- ✅ Accessibility: Screen readers đọc đúng ngôn ngữ
- ✅ `dir="ltr"` - Text direction (left-to-right)

**❌ Không làm:**
```html
<!-- WRONG: No language attribute -->
<html>
```

---

### `<head>` - Metadata Container

```html
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <?php wp_head(); ?>
</head>
```

**✅ Tại sao:**
- ✅ `charset="UTF-8"` - Hỗ trợ Unicode, tiếng Việt
- ✅ `viewport` - Responsive, mobile-friendly (SEO ranking factor)
- ✅ `X-UA-Compatible` - IE compatibility
- ✅ `wp_head()` - WordPress hooks (plugins, SEO tools)

**❌ Không làm:**
```html
<!-- WRONG: Missing viewport -->
<head>
    <meta charset="UTF-8">
</head>
```

---

### `<title>` - Page Title

```php
<title><?php wp_title('|', true, 'right'); bloginfo('name'); ?></title>
```

**✅ Tại sao:**
- ✅ SEO: Quan trọng nhất cho ranking
- ✅ 50-60 ký tự tối ưu
- ✅ Unique cho mỗi page
- ✅ Format: "Page Title | Site Name"

**❌ Không làm:**
```html
<!-- WRONG: Generic title -->
<title>My Website</title>
```

---

## 2. METADATA & SEO

### Meta Description

```html
<meta name="description" content="<?php echo esc_attr(wp_trim_words(get_the_excerpt(), 30)); ?>">
```

**✅ Tại sao:**
- ✅ SEO: Hiển thị trong search results
- ✅ 150-160 ký tự tối ưu
- ✅ Unique cho mỗi page
- ✅ `esc_attr()` - Security: Prevent XSS

**❌ Không làm:**
```html
<!-- WRONG: Same description for all pages -->
<meta name="description" content="Welcome to my site">
```

---

### Canonical URL

```php
<link rel="canonical" href="<?php echo esc_url(get_permalink()); ?>">
```

**✅ Tại sao:**
- ✅ SEO: Tránh duplicate content
- ✅ Chỉ định URL chính thức
- ✅ `esc_url()` - Security: Sanitize URL

---

### Open Graph (Facebook, LinkedIn)

```php
<meta property="og:title" content="<?php echo esc_attr(get_the_title()); ?>">
<meta property="og:description" content="<?php echo esc_attr(wp_trim_words(get_the_excerpt(), 30)); ?>">
<meta property="og:image" content="<?php echo esc_url(get_the_post_thumbnail_url(get_the_ID(), 'large')); ?>">
<meta property="og:url" content="<?php echo esc_url(get_permalink()); ?>">
<meta property="og:type" content="article">
<meta property="og:site_name" content="<?php echo esc_attr(get_bloginfo('name')); ?>">
```

**✅ Tại sao:**
- ✅ SEO: Social media sharing
- ✅ Rich previews trên Facebook, LinkedIn
- ✅ Tăng CTR từ social media
- ✅ `og:image` - Minimum 1200x630px

---

### Twitter Cards

```php
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?php echo esc_attr(get_the_title()); ?>">
<meta name="twitter:description" content="<?php echo esc_attr(wp_trim_words(get_the_excerpt(), 30)); ?>">
<meta name="twitter:image" content="<?php echo esc_url(get_the_post_thumbnail_url(get_the_ID(), 'large')); ?>">
```

**✅ Tại sao:**
- ✅ SEO: Twitter sharing
- ✅ Rich cards với image
- ✅ `summary_large_image` - Best format

---

## 3. NAVIGATION

### `<nav>` - Navigation Container

```php
<nav class="main-navigation" aria-label="<?php esc_attr_e('Menu chính', 'laca'); ?>">
    <?php
    wp_nav_menu([
        'theme_location' => 'primary',
        'menu_class'     => 'nav-menu',
        'menu_id'        => 'primary-menu',
        'container'      => false,
    ]);
    ?>
</nav>
```

**✅ Tại sao:**
- ✅ Semantic HTML5: `<nav>` cho navigation
- ✅ Accessibility: `aria-label` mô tả navigation
- ✅ SEO: Google hiểu cấu trúc site
- ✅ `esc_attr_e()` - i18n + Security

**❌ Không làm:**
```html
<!-- WRONG: Generic div, no ARIA -->
<div class="menu">
    <ul>...</ul>
</div>
```

---

### Menu Toggle Button

```php
<button id="menu-toggle" 
        aria-label="<?php esc_attr_e('Mở menu', 'laca'); ?>" 
        aria-expanded="false" 
        aria-controls="primary-menu">
    <span class="hamburger-icon"></span>
</button>
```

**✅ Tại sao:**
- ✅ Accessibility: `aria-label` cho screen readers
- ✅ `aria-expanded` - Trạng thái menu (open/closed)
- ✅ `aria-controls` - Liên kết với menu ID
- ✅ `<button>` thay vì `<a>` - Semantic đúng

**❌ Không làm:**
```html
<!-- WRONG: Link instead of button, no ARIA -->
<a href="#" class="menu-toggle">☰</a>
```

---

### Breadcrumbs

```php
<nav aria-label="<?php esc_attr_e('Breadcrumb', 'laca'); ?>">
    <ol itemscope itemtype="https://schema.org/BreadcrumbList">
        <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
            <a itemprop="item" href="<?php echo esc_url(home_url('/')); ?>">
                <span itemprop="name"><?php esc_html_e('Trang chủ', 'laca'); ?></span>
            </a>
            <meta itemprop="position" content="1" />
        </li>
        <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
            <span itemprop="name"><?php echo esc_html(get_the_title()); ?></span>
            <meta itemprop="position" content="2" />
        </li>
    </ol>
</nav>
```

**✅ Tại sao:**
- ✅ SEO: Schema.org BreadcrumbList
- ✅ Google hiển thị breadcrumbs trong search results
- ✅ Accessibility: `aria-label="Breadcrumb"`
- ✅ Semantic: `<ol>` cho ordered list

---

## 4. FORMS & INPUTS

### `<form>` - Form Container

```php
<form class="search-form" 
      role="search" 
      aria-label="<?php esc_attr_e('Tìm kiếm', 'laca'); ?>" 
      method="get" 
      action="<?php echo esc_url(home_url('/')); ?>">
    <!-- Form fields -->
</form>
```

**✅ Tại sao:**
- ✅ Accessibility: `role="search"` cho search forms
- ✅ `aria-label` - Mô tả form
- ✅ `method="get"` - SEO friendly cho search
- ✅ `esc_url()` - Security: Sanitize action URL

**❌ Không làm:**
```html
<!-- WRONG: No role, no ARIA -->
<form class="search">
```

---

### `<input>` - Text Input

```php
<label for="search-input" class="screen-reader-text">
    <?php esc_html_e('Từ khóa tìm kiếm', 'laca'); ?>
</label>
<input type="text" 
       id="search-input" 
       name="s"
       placeholder="<?php echo esc_attr__('Tìm kiếm...', 'laca'); ?>" 
       aria-label="<?php esc_attr_e('Nhập từ khóa tìm kiếm', 'laca'); ?>"
       autocomplete="off"
       required>
```

**✅ Tại sao:**
- ✅ Accessibility: `<label>` liên kết với `id`
- ✅ `screen-reader-text` - Ẩn visual nhưng screen reader đọc được
- ✅ `aria-label` - Mô tả thêm cho screen readers
- ✅ `placeholder` - Visual hint (không thay thế label)
- ✅ `autocomplete` - UX: Tắt autocomplete nếu không cần
- ✅ `required` - HTML5 validation
- ✅ `esc_attr__()` - i18n + Security

**❌ Không làm:**
```html
<!-- WRONG: No label, placeholder as label -->
<input type="text" placeholder="Search">
```

---

### `<input type="checkbox">` - Checkbox/Switch

```php
<label class="toggle-label">
    <input type="checkbox" 
           id="dark-mode-toggle"
           aria-label="<?php esc_attr_e('Chuyển chế độ tối', 'laca'); ?>" 
           role="switch" 
           aria-checked="false">
    <span class="screen-reader-text">
        <?php esc_html_e('Chuyển chế độ tối/sáng', 'laca'); ?>
    </span>
    <span class="toggle-slider"></span>
</label>
```

**✅ Tại sao:**
- ✅ Accessibility: `role="switch"` cho toggle switches
- ✅ `aria-checked` - Trạng thái (true/false)
- ✅ `aria-label` - Mô tả cho screen readers
- ✅ `screen-reader-text` - Text chỉ cho screen readers
- ✅ `<label>` wrap input - Click anywhere to toggle

**❌ Không làm:**
```html
<!-- WRONG: No ARIA, no role -->
<input type="checkbox">
```

---

### `<button>` - Button

```php
<!-- Submit button -->
<button type="submit" 
        aria-label="<?php esc_attr_e('Gửi form', 'laca'); ?>">
    <?php esc_html_e('Gửi', 'laca'); ?>
</button>

<!-- Reset button -->
<button type="reset" 
        aria-label="<?php esc_attr_e('Xóa tìm kiếm', 'laca'); ?>">
    <span aria-hidden="true">×</span>
    <span class="screen-reader-text"><?php esc_html_e('Xóa', 'laca'); ?></span>
</button>

<!-- Regular button -->
<button type="button" 
        onclick="handleClick()" 
        aria-label="<?php esc_attr_e('Đóng modal', 'laca'); ?>">
    <?php esc_html_e('Đóng', 'laca'); ?>
</button>
```

**✅ Tại sao:**
- ✅ `type="submit|reset|button"` - Explicit type
- ✅ `aria-label` - Mô tả action
- ✅ `aria-hidden="true"` - Ẩn icon decorative khỏi screen readers
- ✅ `screen-reader-text` - Text alternative cho icons
- ✅ Accessibility: Keyboard accessible by default

**❌ Không làm:**
```html
<!-- WRONG: No type, icon only -->
<button>×</button>
```

---

### `<select>` - Dropdown

```php
<label for="category-select">
    <?php esc_html_e('Chọn danh mục', 'laca'); ?>
</label>
<select id="category-select" 
        name="category" 
        aria-label="<?php esc_attr_e('Chọn danh mục', 'laca'); ?>"
        required>
    <option value=""><?php esc_html_e('-- Chọn --', 'laca'); ?></option>
    <option value="tech"><?php esc_html_e('Công nghệ', 'laca'); ?></option>
    <option value="design"><?php esc_html_e('Thiết kế', 'laca'); ?></option>
</select>
```

**✅ Tại sao:**
- ✅ Accessibility: `<label>` với `for` attribute
- ✅ `aria-label` - Mô tả thêm
- ✅ Empty first option - UX: Force user to choose
- ✅ `required` - HTML5 validation

---

## 5. IMAGES & MEDIA

### `<img>` - Image (Standard)

```php
<img src="<?php echo esc_url(get_the_post_thumbnail_url(get_the_ID(), 'large')); ?>" 
     alt="<?php echo esc_attr(get_the_title()); ?>" 
     width="1200" 
     height="630" 
     loading="lazy" 
     decoding="async"
     srcset="<?php echo esc_attr(wp_get_attachment_image_srcset(get_post_thumbnail_id())); ?>"
     sizes="(max-width: 768px) 100vw, (max-width: 1200px) 50vw, 1200px">
```

**✅ Tại sao:**
- ✅ SEO: `alt` text mô tả image (bắt buộc)
- ✅ Performance: `loading="lazy"` - Native lazy loading
- ✅ Performance: `decoding="async"` - Non-blocking decode
- ✅ Performance: `srcset` - Responsive images
- ✅ Performance: `sizes` - Browser chọn image phù hợp
- ✅ Accessibility: `width` & `height` - Prevent layout shift (CLS)
- ✅ Security: `esc_url()`, `esc_attr()` - Sanitize

**❌ Không làm:**
```html
<!-- WRONG: No alt, no lazy loading, no dimensions -->
<img src="image.jpg">
```

---

### `<picture>` - Responsive Image với WebP

```php
<picture>
    <source srcset="<?php echo esc_url(str_replace('.jpg', '.webp', $image_url)); ?>" 
            type="image/webp">
    <source srcset="<?php echo esc_url($image_url); ?>" 
            type="image/jpeg">
    <img src="<?php echo esc_url($image_url); ?>" 
         alt="<?php echo esc_attr(get_the_title()); ?>" 
         width="1200" 
         height="630" 
         loading="lazy" 
         decoding="async">
</picture>
```

**✅ Tại sao:**
- ✅ Performance: WebP format (-30% file size)
- ✅ Fallback: JPEG cho browsers cũ
- ✅ SEO: Google ưu tiên WebP
- ✅ `<source>` order matters - WebP first

---

### `<figure>` & `<figcaption>` - Image với Caption

```php
<figure>
    <img src="<?php echo esc_url($image_url); ?>" 
         alt="<?php echo esc_attr($alt_text); ?>" 
         loading="lazy">
    <figcaption><?php echo esc_html($caption); ?></figcaption>
</figure>
```

**✅ Tại sao:**
- ✅ Semantic HTML5: `<figure>` cho self-contained content
- ✅ SEO: `<figcaption>` indexed by Google
- ✅ Accessibility: Caption bổ sung cho alt text

---

### `<video>` - Video Element

```php
<video width="1920" 
       height="1080" 
       controls 
       preload="metadata" 
       poster="<?php echo esc_url($poster_url); ?>"
       aria-label="<?php esc_attr_e('Video giới thiệu', 'laca'); ?>">
    <source src="<?php echo esc_url($video_url); ?>" type="video/mp4">
    <source src="<?php echo esc_url($video_webm_url); ?>" type="video/webm">
    <track kind="captions" 
           src="<?php echo esc_url($captions_url); ?>" 
           srclang="vi" 
           label="Tiếng Việt">
    <p><?php esc_html_e('Trình duyệt của bạn không hỗ trợ video.', 'laca'); ?></p>
</video>
```

**✅ Tại sao:**
- ✅ Accessibility: `<track>` cho captions/subtitles
- ✅ Performance: `preload="metadata"` - Chỉ load metadata
- ✅ UX: `poster` - Thumbnail trước khi play
- ✅ `aria-label` - Mô tả video
- ✅ Multiple `<source>` - Format fallback
- ✅ Fallback text cho browsers không hỗ trợ

**❌ Không làm:**
```html
<!-- WRONG: No captions, autoplay -->
<video src="video.mp4" autoplay></video>
```

---

## 6. LINKS & BUTTONS

### `<a>` - Link (Internal)

```php
<a href="<?php echo esc_url(get_permalink()); ?>" 
   aria-label="<?php echo esc_attr(sprintf(__('Đọc thêm về %s', 'laca'), get_the_title())); ?>">
    <?php esc_html_e('Đọc thêm', 'laca'); ?>
</a>
```

**✅ Tại sao:**
- ✅ SEO: Internal links quan trọng
- ✅ Accessibility: `aria-label` mô tả destination
- ✅ Avoid "Click here" - Dùng descriptive text
- ✅ `esc_url()` - Security: Sanitize URL

---

### `<a>` - Link (External)

```php
<a href="<?php echo esc_url($external_url); ?>" 
   target="_blank" 
   rel="noopener noreferrer"
   aria-label="<?php echo esc_attr(sprintf(__('Mở %s trong tab mới', 'laca'), $link_text)); ?>">
    <?php echo esc_html($link_text); ?>
    <span class="screen-reader-text"><?php esc_html_e('(mở trong tab mới)', 'laca'); ?></span>
</a>
```

**✅ Tại sao:**
- ✅ Security: `rel="noopener noreferrer"` - Prevent tabnabbing
- ✅ Accessibility: Thông báo "mở tab mới"
- ✅ `target="_blank"` - Mở tab mới
- ✅ `aria-label` - Mô tả đầy đủ

**❌ Không làm:**
```html
<!-- WRONG: target="_blank" without rel -->
<a href="https://example.com" target="_blank">Link</a>
```

---

### `<a>` - Skip Link

```php
<a class="skip-link screen-reader-text" 
   href="#main-content">
    <?php esc_html_e('Bỏ qua đến nội dung chính', 'laca'); ?>
</a>
```

**✅ Tại sao:**
- ✅ Accessibility: WCAG 2.1 Level A requirement
- ✅ Keyboard users skip navigation
- ✅ `screen-reader-text` - Visible on focus
- ✅ Link to `#main-content` ID

**CSS Required:**
```css
.skip-link {
    &:focus {
        clip: auto !important;
        clip-path: none;
        display: block;
        z-index: 100000;
    }
}
```

---

## 7. TEXT CONTENT

### `<h1>` - Main Heading

```php
<?php if (is_home() || is_front_page()): ?>
    <h1 class="site-name screen-reader-text">
        <?php echo esc_html(get_bloginfo('name')); ?>
    </h1>
<?php endif; ?>

<?php if (is_singular()): ?>
    <h1><?php echo esc_html(get_the_title()); ?></h1>
<?php endif; ?>
```

**✅ Tại sao:**
- ✅ SEO: Chỉ 1 `<h1>` per page
- ✅ Homepage: Site name là H1 (hidden visually)
- ✅ Single post: Post title là H1
- ✅ Accessibility: Heading hierarchy (h1→h2→h3)

**❌ Không làm:**
```html
<!-- WRONG: Multiple H1 -->
<h1>Site Name</h1>
<h1>Page Title</h1>
```

---

### `<h2>` - `<h6>` - Subheadings

```php
<h2><?php esc_html_e('Bài viết liên quan', 'laca'); ?></h2>
<h3><?php echo esc_html(get_the_title()); ?></h3>
```

**✅ Tại sao:**
- ✅ SEO: Heading hierarchy quan trọng
- ✅ Accessibility: Screen readers navigate by headings
- ✅ Không skip levels (h1→h3 ❌, h1→h2→h3 ✅)

---

### `<p>` - Paragraph

```php
<p><?php echo wp_kses_post(get_the_excerpt()); ?></p>
```

**✅ Tại sao:**
- ✅ `wp_kses_post()` - Security: Allow safe HTML tags
- ✅ Semantic: `<p>` cho paragraphs

---

### `<strong>` vs `<b>`, `<em>` vs `<i>`

```html
<!-- Semantic emphasis -->
<strong>Quan trọng</strong> <!-- SEO weight -->
<em>Nhấn mạnh</em> <!-- Emphasis -->

<!-- Visual only -->
<b>Bold text</b> <!-- No semantic meaning -->
<i>Italic text</i> <!-- No semantic meaning -->
```

**✅ Tại sao:**
- ✅ SEO: `<strong>` có semantic weight
- ✅ Accessibility: Screen readers emphasize `<em>`
- ✅ `<b>`, `<i>` chỉ visual, không semantic

---

## 8. INTERACTIVE ELEMENTS

### `<details>` & `<summary>` - Accordion

```php
<details>
    <summary><?php esc_html_e('Câu hỏi thường gặp', 'laca'); ?></summary>
    <p><?php echo wp_kses_post($answer); ?></p>
</details>
```

**✅ Tại sao:**
- ✅ Semantic HTML5: Native accordion
- ✅ Accessibility: Keyboard accessible
- ✅ Zero JavaScript required
- ✅ SEO: Content indexed by Google

---

### `<dialog>` - Modal

```php
<dialog id="modal" aria-labelledby="modal-title" aria-modal="true">
    <h2 id="modal-title"><?php esc_html_e('Tiêu đề Modal', 'laca'); ?></h2>
    <p><?php echo wp_kses_post($content); ?></p>
    <button type="button" 
            onclick="document.getElementById('modal').close()"
            aria-label="<?php esc_attr_e('Đóng modal', 'laca'); ?>">
        <?php esc_html_e('Đóng', 'laca'); ?>
    </button>
</dialog>
```

**✅ Tại sao:**
- ✅ Semantic HTML5: Native modal
- ✅ Accessibility: `aria-modal="true"`
- ✅ `aria-labelledby` - Link to title
- ✅ Focus trap built-in

---

### Live Regions (AJAX Content)

```php
<div class="search-results" 
     role="status" 
     aria-live="polite" 
     aria-atomic="true">
    <!-- AJAX results here -->
</div>
```

**✅ Tại sao:**
- ✅ Accessibility: Screen readers announce updates
- ✅ `role="status"` - Status messages
- ✅ `aria-live="polite"` - Không interrupt user
- ✅ `aria-atomic="true"` - Đọc toàn bộ content

**Variants:**
- `aria-live="assertive"` - Urgent updates (errors)
- `aria-live="off"` - No announcements

---

## 9. LISTS

### `<ul>` - Unordered List

```php
<ul>
    <li><?php esc_html_e('Item 1', 'laca'); ?></li>
    <li><?php esc_html_e('Item 2', 'laca'); ?></li>
</ul>
```

**✅ Tại sao:**
- ✅ Semantic: Unordered items
- ✅ Accessibility: Screen readers announce "list, 2 items"

---

### `<ol>` - Ordered List

```php
<ol>
    <li><?php esc_html_e('Bước 1', 'laca'); ?></li>
    <li><?php esc_html_e('Bước 2', 'laca'); ?></li>
</ol>
```

**✅ Tại sao:**
- ✅ Semantic: Sequential steps
- ✅ SEO: Google hiểu ordered content

---

### `<dl>`, `<dt>`, `<dd>` - Description List

```php
<dl>
    <dt><?php esc_html_e('Tên sản phẩm', 'laca'); ?></dt>
    <dd><?php echo esc_html($product_name); ?></dd>
    
    <dt><?php esc_html_e('Giá', 'laca'); ?></dt>
    <dd><?php echo esc_html($price); ?></dd>
</dl>
```

**✅ Tại sao:**
- ✅ Semantic: Key-value pairs
- ✅ SEO: Structured data

---

## 10. TABLES

### `<table>` - Data Table

```php
<table>
    <caption><?php esc_html_e('Bảng giá sản phẩm', 'laca'); ?></caption>
    <thead>
        <tr>
            <th scope="col"><?php esc_html_e('Sản phẩm', 'laca'); ?></th>
            <th scope="col"><?php esc_html_e('Giá', 'laca'); ?></th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <th scope="row"><?php echo esc_html($product_name); ?></th>
            <td><?php echo esc_html($price); ?></td>
        </tr>
    </tbody>
</table>
```

**✅ Tại sao:**
- ✅ Accessibility: `<caption>` mô tả table
- ✅ `scope="col"` - Column headers
- ✅ `scope="row"` - Row headers
- ✅ `<thead>`, `<tbody>` - Semantic structure

**❌ Không làm:**
```html
<!-- WRONG: No caption, no scope -->
<table>
    <tr><td>Data</td></tr>
</table>
```

---

## 11. SEMANTIC HTML5

### `<header>` - Page/Section Header

```php
<header id="masthead" role="banner">
    <div class="site-branding">
        <?php the_custom_logo(); ?>
    </div>
    <nav role="navigation">...</nav>
</header>
```

**✅ Tại sao:**
- ✅ Semantic HTML5: `<header>` cho header
- ✅ `role="banner"` - ARIA landmark
- ✅ SEO: Google hiểu cấu trúc

---

### `<main>` - Main Content

```php
<main id="main-content" role="main">
    <?php while (have_posts()): the_post(); ?>
        <article>...</article>
    <?php endwhile; ?>
</main>
```

**✅ Tại sao:**
- ✅ Accessibility: Chỉ 1 `<main>` per page
- ✅ `role="main"` - ARIA landmark
- ✅ Skip link target: `id="main-content"`

---

### `<article>` - Independent Content

```php
<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
    <header class="entry-header">
        <h2><?php the_title(); ?></h2>
    </header>
    <div class="entry-content">
        <?php the_content(); ?>
    </div>
</article>
```

**✅ Tại sao:**
- ✅ Semantic: Self-contained content
- ✅ SEO: Google hiểu article structure
- ✅ `post_class()` - WordPress classes

---

### `<aside>` - Sidebar/Related Content

```php
<aside role="complementary" aria-label="<?php esc_attr_e('Sidebar', 'laca'); ?>">
    <?php dynamic_sidebar('sidebar-1'); ?>
</aside>
```

**✅ Tại sao:**
- ✅ Semantic: Tangentially related content
- ✅ `role="complementary"` - ARIA landmark
- ✅ `aria-label` - Mô tả sidebar

---

### `<footer>` - Page/Section Footer

```php
<footer id="colophon" role="contentinfo">
    <p>&copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?></p>
</footer>
```

**✅ Tại sao:**
- ✅ Semantic HTML5: `<footer>` cho footer
- ✅ `role="contentinfo"` - ARIA landmark

---

### `<section>` - Thematic Grouping

```php
<section aria-labelledby="section-title">
    <h2 id="section-title"><?php esc_html_e('Bài viết mới', 'laca'); ?></h2>
    <!-- Content -->
</section>
```

**✅ Tại sao:**
- ✅ Semantic: Thematic content
- ✅ `aria-labelledby` - Link to heading
- ✅ Always có heading trong `<section>`

---

## 🔒 SECURITY CHECKLIST

### WordPress Escaping Functions

```php
// Output trong HTML
<?php echo esc_html($text); ?>

// Output trong attributes
<div class="<?php echo esc_attr($class); ?>">

// Output URLs
<a href="<?php echo esc_url($url); ?>">

// Output JavaScript
<script>var data = <?php echo wp_json_encode($data); ?>;</script>

// Output HTML (allow safe tags)
<?php echo wp_kses_post($content); ?>

// Translation + escaping
<?php esc_html_e('Text', 'domain'); ?>
<?php echo esc_html__('Text', 'domain'); ?>
<?php esc_attr_e('Text', 'domain'); ?>
```

**✅ Luôn luôn:**
- ✅ Escape ALL output
- ✅ Sanitize ALL input
- ✅ Validate ALL data

---

## ⚡ PERFORMANCE CHECKLIST

### Critical Optimizations

```html
<!-- Preconnect to external domains -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="dns-prefetch" href="https://fonts.googleapis.com">

<!-- Preload critical resources -->
<link rel="preload" href="/path/to/font.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="/path/to/critical.css" as="style">

<!-- Async/Defer scripts -->
<script src="script.js" defer></script>
<script src="analytics.js" async></script>

<!-- Lazy load images -->
<img src="image.jpg" loading="lazy" decoding="async">

<!-- Resource hints -->
<link rel="prefetch" href="/next-page.html">
```

---

## ♿ ACCESSIBILITY CHECKLIST

### WCAG 2.1 Level AA Requirements

- ✅ All images có `alt` text
- ✅ All forms có `<label>`
- ✅ All buttons có descriptive text hoặc `aria-label`
- ✅ Color contrast >= 4.5:1 (text), >= 3:1 (UI)
- ✅ Keyboard navigation works 100%
- ✅ Focus visible styles
- ✅ Skip link present
- ✅ Heading hierarchy đúng (h1→h2→h3)
- ✅ ARIA labels cho interactive elements
- ✅ Live regions cho dynamic content

---

## 📚 TÀI LIỆU THAM KHẢO

- **WCAG 2.1:** https://www.w3.org/WAI/WCAG21/quickref/
- **MDN Web Docs:** https://developer.mozilla.org/
- **WordPress Codex:** https://codex.wordpress.org/
- **Schema.org:** https://schema.org/
- **Google SEO:** https://developers.google.com/search/docs

---

**Last Updated:** December 14, 2025  
**Version:** 1.0  
**Author:** LacaDev Team
