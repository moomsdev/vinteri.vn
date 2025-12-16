# Migration Guide - Responsive Images

## Tổng quan

File mới: `app/helpers/responsive-images.php` chứa các hàm helper tự động tạo responsive images với `srcset` và `sizes`.

## So sánh Old vs New

### 1. Post Thumbnail

**❌ CŨ (Không responsive):**
```php
<?php thePostThumbnailUrl(480, 360); ?>
```

**✅ MỚI (Tự động responsive):**
```php
<?php theResponsivePostThumbnail('mobile'); ?>
```

---

### 2. Post Meta Image

**❌ CŨ:**
```php
<?php thePostMetaImageUrl('gallery_image', 480, 360); ?>
```

**✅ MỚI:**
```php
<?php theResponsivePostMeta('gallery_image', 'mobile'); ?>
```

---

### 3. Theme Option Image

**❌ CŨ:**
```php
<?php theOptionImage('site_logo', 200, 100); ?>
```

**✅ MỚI:**
```php
<?php theResponsiveOption('site_logo', 'full'); ?>
```

---

### 4. Direct Attachment ID

**❌ CŨ:**
```php
<img src="<?php echo getImageUrlById($image_id, 480, 360); ?>">
```

**✅ MỚI:**
```php
<?php theResponsiveImage($image_id, 'mobile'); ?>
```

---

## Available Sizes

- `'mobile'` - 480px (cho mobile)
- `'mobile-2x'` - 960px (cho mobile retina)
- `'tablet'` - 768px (cho tablet)
- `'tablet-2x'` - 1536px (cho tablet retina)
- `'full'` - Ảnh gốc (cho desktop/laptop)

---

## Custom Attributes

Tất cả hàm mới đều hỗ trợ custom attributes:

```php
<?php 
theResponsivePostThumbnail('mobile', [
    'class' => 'post-thumb',
    'loading' => 'lazy',
    'fetchpriority' => 'high',
    'alt' => 'Custom alt text',
]); 
?>
```

---

## Danh sách hàm mới

### Echo Functions (In ra HTML)
- `theResponsivePostThumbnail($size, $attr)`
- `theResponsivePostMeta($meta_key, $size, $attr)`
- `theResponsiveOption($option_key, $size, $attr)`
- `theResponsiveImage($attachment_id, $size, $attr)`

### Get Functions (Trả về HTML string)
- `getResponsivePostThumbnail($post_id, $size, $attr)`
- `getResponsivePostMeta($meta_key, $post_id, $size, $attr)`
- `getResponsiveOption($option_key, $size, $attr)`
- `getResponsiveImage($attachment_id, $size, $attr)`

---

## Lợi ích

✅ **Tự động srcset** - Trình duyệt chọn size phù hợp  
✅ **Tự động WebP** - Sử dụng ảnh đã convert  
✅ **Tự động lazy loading** - WordPress tự thêm  
✅ **Tiết kiệm băng thông** - Mobile tải ảnh nhỏ hơn  
✅ **SEO tốt hơn** - Google ưu tiên responsive images  

---

## Migration Steps

1. **Tìm kiếm** các hàm cũ trong theme:
   - `thePostThumbnailUrl`
   - `thePostMetaImageUrl`
   - `theOptionImage`
   - `getImageUrlById`

2. **Thay thế** bằng hàm mới tương ứng

3. **Đổi tham số** từ `($width, $height)` → `($size)`

4. **Test** trên mobile/tablet/desktop

---

## Ví dụ thực tế

### Before:
```php
<div class="post-card">
    <img src="<?php thePostThumbnailUrl(480, 360); ?>" alt="Post">
</div>
```

### After:
```php
<div class="post-card">
    <?php theResponsivePostThumbnail('mobile', ['alt' => 'Post', 'loading' => 'lazy']); ?>
</div>
```

### HTML Output:
```html
<div class="post-card">
    <img src="post-480x360.webp"
         srcset="post-480x360.webp 480w,
                 post-960x720.webp 960w,
                 post-768x576.webp 768w,
                 post.webp 2048w"
         sizes="(max-width: 480px) 480px, (max-width: 768px) 768px, 100vw"
         alt="Post"
         loading="lazy">
</div>
```
