# mooms_dev_v1 Theme

Theme WordPress hiện đại, tập trung hiệu năng, bảo mật và trải nghiệm quản trị.

## Tính năng chính
- Auth chuẩn JSON (login/register/reset) + Google OAuth trên `/wp-login.php`
- MMS Admin & Tools: Security Headers, Resource Hints, Database Cleanup
- Caching & DB: Transient caching, query optimization, auto cleanup
- Pipeline build (đề xuất): critical CSS, service worker, hashed assets

## Hiệu năng
- Loại bỏ bloat (emoji, migrate, assets thừa), defer/async scripts
- Resource Hints: preconnect/dns-prefetch/prefetch theo ngữ cảnh
- Image optimization (WebP/quality/resize theo option)
- Hướng tới LCP/TTI/CLS tốt và điểm PSI 90+

## SEO
- HTML5 semantic, breadcrumbs, lazyload images
- Preload fonts/critical assets, meta cơ bản
- Tối ưu tốc độ tải trang → cải thiện crawl & Core Web Vitals

## Tài liệu
- Tổng hợp: `documents/DOC.md`
- Auth: `documents/AUTH_GUIDE.md`
- Admin & Tools: `documents/ADMIN_GUIDE.md`
- Caching & Queries: `documents/CACHING_GUIDE.md`
- Build: `documents/BUILD_GUIDE.md`
