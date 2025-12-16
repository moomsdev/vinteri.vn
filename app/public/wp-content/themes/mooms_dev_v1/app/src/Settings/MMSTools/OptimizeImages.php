<?php

namespace App\Settings\MMSTools;

class OptimizeImages
{
    protected $currentUser;

	protected $superUsers = SUPER_USER;

	protected $errorMessage = '';

    public function __construct()
    {
        $this->currentUser = wp_get_current_user();

        // Khởi tạo các cài đặt mặc định
        $this->initializeSettings();

        // Hook vào quá trình upload
        add_filter('wp_handle_upload_prefilter', [self::class, 'compress_image_on_upload']);
        add_filter('wp_handle_upload', [self::class, 'compress_and_convert_to_webp']);
        
        // Mark image as optimized after successful upload and processing
        add_filter('wp_generate_attachment_metadata', [self::class, 'mark_as_optimized_on_upload'], 10, 2);

        // AJAX: Bulk optimize theo batch (chỉ admin)
        add_action('wp_ajax_mms_bulk_optimize_images', [self::class, 'ajax_bulk_optimize_images']);
        
        // AJAX: Test single image optimization
        add_action('wp_ajax_mms_test_single_optimize', [self::class, 'ajax_test_single_optimize']);
        
        // AJAX: Restore image to original
        add_action('wp_ajax_mms_restore_image', [self::class, 'ajax_restore_image']);
        
        // AJAX: Get list of images for selection
        add_action('wp_ajax_mms_get_images_list', [self::class, 'ajax_get_images_list']);
        
        // AJAX: Optimize selected images
        add_action('wp_ajax_mms_optimize_selected', [self::class, 'ajax_optimize_selected']);
        
        // AJAX: Bulk restore images
        add_action('wp_ajax_mms_bulk_restore_images', [self::class, 'ajax_bulk_restore_images']);

        // WP-CLI command: wp mms optimize-images --min_kb=300 --limit=200
        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::add_command('mms optimize-images', [self::class, 'cli_optimize_images']);
        }
    }

    /**
     * Khởi tạo các cài đặt mặc định
     */
    private function initializeSettings()
    {
        // Các cài đặt sẽ được lưu vào database khi cần thiết
        if (!get_option('_jpg_quality')) {
            update_option('_jpg_quality', 85);
        }
        if (!get_option('_png_compression')) {
            update_option('_png_compression', 6);
        }
        if (!get_option('_webp_quality')) {
            update_option('_webp_quality', 85);
        }
        if (!get_option('_min_size_saving')) {
            update_option('_min_size_saving', 10);
        }
        if (!get_option('_max_width')) {
            update_option('_max_width', 2048);
        }
        if (!get_option('_max_height')) {
            update_option('_max_height', 2048);
        }
    }

    /**
     * Lấy cấu hình nén hình ảnh
     */
    public static function get_compression_settings()
    {
        $enable_compression_image = get_option('_enable_compression_image') === 'yes';
        $enable_webp_conversion = get_option('_enable_webp_conversion') === 'yes';
        $jpg_quality = get_option('_jpg_quality', 85);
        $png_compression = get_option('_png_compression', 6);
        $webp_quality = get_option('_webp_quality', 85);
        $min_size_saving = get_option('_min_size_saving', 10);
        $max_width = get_option('_max_width', 2048);
        $max_height = get_option('_max_height', 2048);
        $preserve_original = get_option('_preserve_original') === 'yes';

        return [
            'jpg_quality' => intval($jpg_quality),        // Chất lượng JPG (0-100)
            'png_compression' => intval($png_compression),     // Mức nén PNG (0-9)
            'webp_quality' => intval($webp_quality),       // Chất lượng WebP (0-100)
            'min_size_saving' => intval($min_size_saving),    // Tỷ lệ tiết kiệm tối thiểu để chuyển WebP (%)
            'max_width' => intval($max_width),        // Chiều rộng tối đa (px)
            'max_height' => intval($max_height),       // Chiều cao tối đa (px)
            'enable_webp_conversion' => $enable_webp_conversion,
            'enable_compression_image' => $enable_compression_image,
            'preserve_original' => $preserve_original, // Có giữ file gốc không
        ];
    }

    /**
     * Lấy cấu hình từ theme options hoặc sử dụng mặc định
     */
    public static function get_settings()
    {
        $default_settings = self::get_compression_settings();
        
        // Có thể lấy từ theme options nếu có
        $theme_settings = get_option('moomsdev_image_optimization', []);
        
        return wp_parse_args($theme_settings, $default_settings);
    }

    /**
     * Kiểm tra xem có nên bật tối ưu hóa không
     */
    public static function should_optimize()
    {
        $settings = self::get_settings();
        return $settings['enable_compression_image'] || $settings['enable_webp_conversion'];
    }

    /**
     * Kiểm tra hỗ trợ WebP
     */
    public static function supports_webp()
    {
        return function_exists('imagewebp') && function_exists('imagecreatefromwebp');
    }

    /**
     * Kiểm tra hỗ trợ GD
     */
    public static function supports_gd()
    {
        return extension_loaded('gd') && function_exists('gd_info');
    }

    /**
     * Lấy thông tin hỗ trợ
     */
    public static function get_support_info()
    {
        return [
            'gd' => self::supports_gd(),
            'webp' => self::supports_webp(),
            'jpeg' => function_exists('imagecreatefromjpeg'),
            'png' => function_exists('imagecreatefrompng'),
        ];
    }

    /**
     * Tối ưu 1 attachment theo ID (dùng lại logic upload)
     * Trả về true nếu có thay đổi kích thước/định dạng
     */
    public static function optimize_attachment_by_id($attachmentId, $skipIfOptimized = true)
    {
        // Check xem compression có được bật không
        if (!self::should_optimize()) {
            error_log("MMS Optimize: Skipping ID $attachmentId - Compression/WebP conversion is DISABLED in settings");
            return false;
        }
        
        // Force refresh cache trước khi check
        wp_cache_delete($attachmentId, 'post_meta');
        
        // Skip nếu đã optimize (trừ khi force)
        $alreadyOptimized = get_post_meta($attachmentId, '_mms_optimized', true);
        if ($skipIfOptimized && $alreadyOptimized) {
            error_log("MMS Optimize: Skipping ID $attachmentId - already optimized at: $alreadyOptimized");
            return false;
        }

        $file = get_attached_file($attachmentId);
        if (!$file || !file_exists($file)) {
            error_log("MMS Optimize: Skipping ID $attachmentId - file not found: $file");
            return false;
        }

        $type = wp_check_filetype($file);
        if (empty($type['type']) || strpos($type['type'], 'image/') !== 0) {
            error_log("MMS Optimize: Skipping ID $attachmentId - not an image: " . ($type['type'] ?? 'unknown'));
            return false;
        }

        $beforeSize = @filesize($file) ?: 0;
        error_log("MMS Optimize: Starting optimization for ID $attachmentId - size: " . size_format($beforeSize));

        // Tạo backup file gốc
        $backupPath = $file . '.mms-backup';
        if (!file_exists($backupPath)) {
            copy($file, $backupPath);
            update_post_meta($attachmentId, '_mms_backup_path', $backupPath);
            update_post_meta($attachmentId, '_mms_original_size', $beforeSize);
        }

        $mockUpload = [
            'file' => $file,
            'type' => $type['type'],
            'url'  => wp_get_attachment_url($attachmentId),
        ];

        // Nén file tạm thời tại chỗ (prefilter kiểu upload)
        $tmp = [
            'name'     => basename($file),
            'type'     => $type['type'],
            'tmp_name' => $file,
        ];
        self::compress_image_on_upload($tmp);

        // Cố gắng convert sang WebP nếu hiệu quả
        $result = self::compress_and_convert_to_webp($mockUpload);

        $afterPath = $result['file'] ?? $file;
        $afterSize = @filesize($afterPath) ?: 0;

        // Nếu đổi file (webp) thì cập nhật attachment
        if (!empty($result['file']) && $result['file'] !== $file) {
            update_attached_file($attachmentId, $result['file']);
            
            // TẮT hook mark_as_optimized trước khi regenerate metadata (nếu có)
            $has_hook = has_filter('wp_generate_attachment_metadata', [self::class, 'mark_as_optimized_on_upload']);
            if ($has_hook !== false) {
                remove_filter('wp_generate_attachment_metadata', [self::class, 'mark_as_optimized_on_upload'], 10);
            }
            
            // Cập nhật metadata để WordPress nhận diện lại
            $meta = wp_generate_attachment_metadata($attachmentId, $result['file']);
            if (!is_wp_error($meta) && !empty($meta)) {
                wp_update_attachment_metadata($attachmentId, $meta);
            }
            
            // BẬT LẠI hook chỉ nếu đã remove trước đó
            if ($has_hook !== false) {
                add_filter('wp_generate_attachment_metadata', [self::class, 'mark_as_optimized_on_upload'], 10, 2);
            }
        }

        // Ghi dấu đã tối ưu với thông tin chi tiết
        update_post_meta($attachmentId, '_mms_optimized', current_time('mysql'));
        update_post_meta($attachmentId, '_mms_optimized_size', $afterSize);
        update_post_meta($attachmentId, '_mms_saved_bytes', max(0, $beforeSize - $afterSize));
        
        // Force clear cache sau khi update meta
        wp_cache_delete($attachmentId, 'post_meta');
        
        $saved = $beforeSize - $afterSize;
        error_log("MMS Optimize: Completed ID $attachmentId - before: " . size_format($beforeSize) . ", after: " . size_format($afterSize) . ", saved: " . size_format($saved));

        return $afterSize < $beforeSize;
    }

    /**
     * Bulk optimize theo batch
     * @return array { processed, optimized, next_offset, done }
     */
    public static function bulk_optimize($minSizeKB = 300, $limit = 50, $offset = 0)
    {
        $args = [
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => $limit,
            'offset'         => $offset,
            'post_mime_type' => ['image/jpeg', 'image/png'],
            'fields'         => 'ids',
        ];

        $q = new \WP_Query($args);
        $ids = (array) $q->posts;

        $processed = 0;
        $optimized = 0;

        foreach ($ids as $id) {
            $file = get_attached_file($id);
            if (!$file || !file_exists($file)) {
                error_log("MMS Bulk: Skipping ID $id - file not found");
                $processed++;
                continue;
            }

            // Force refresh meta cache trước khi check
            wp_cache_delete($id, 'post_meta');
            $isOptimized = get_post_meta($id, '_mms_optimized', true);
            
            // Bỏ qua nếu đã tối ưu trước đó
            if ($isOptimized) {
                error_log("MMS Bulk: Skipping ID $id - already optimized at: $isOptimized");
                $processed++;
                continue;
            }

            $sizeKB = (@filesize($file) ?: 0) / 1024;
            if ($sizeKB < $minSizeKB) {
                error_log("MMS Bulk: Skipping ID $id - too small: " . round($sizeKB, 2) . " KB (min: $minSizeKB KB)");
                $processed++;
                continue;
            }

            error_log("MMS Bulk: Processing ID $id - size: " . round($sizeKB, 2) . " KB");
            $changed = self::optimize_attachment_by_id($id);
            if ($changed) {
                error_log("MMS Bulk: Successfully optimized ID $id");
                $optimized++;
            } else {
                error_log("MMS Bulk: No change for ID $id (already optimal or error)");
            }
            $processed++;
        }

        $nextOffset = $offset + $processed;
        $done = ($processed < $limit) || ($q->found_posts <= $nextOffset);

        return [
            'processed'   => $processed,
            'optimized'   => $optimized,
            'next_offset' => $nextOffset,
            'done'        => $done,
            'total'       => (int) $q->found_posts,
        ];
    }

    /**
     * AJAX handler: mms_bulk_optimize_images
     * Yêu cầu: manage_options + nonce + confirm=yes
     */
    public static function ajax_bulk_optimize_images()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied'], 403);
        }
        check_ajax_referer('mms_bulk_optimize_images', 'nonce');

        $confirm   = isset($_POST['confirm']) ? sanitize_text_field((string) $_POST['confirm']) : '';
        if ($confirm !== 'yes') {
            wp_send_json_error(['message' => 'Confirmation required (confirm=yes)'], 400);
        }

        $minKB = isset($_POST['min_kb']) ? max(1, (int) $_POST['min_kb']) : 300;
        $limit = isset($_POST['limit']) ? max(1, min(200, (int) $_POST['limit'])) : 50;
        $offset= isset($_POST['offset']) ? max(0, (int) $_POST['offset']) : 0;

        $result = self::bulk_optimize($minKB, $limit, $offset);
        wp_send_json_success($result);
    }

    /**
     * WP-CLI: wp mms optimize-images --min_kb=300 --limit=200
     */
    public static function cli_optimize_images($args, $assoc_args)
    {
        $minKB = isset($assoc_args['min_kb']) ? (int) $assoc_args['min_kb'] : 300;
        $limit = isset($assoc_args['limit']) ? (int) $assoc_args['limit'] : 200;

        $offset = 0;
        $totalOptimized = 0;
        $totalProcessed = 0;

        while (true) {
            $batch = self::bulk_optimize($minKB, $limit, $offset);
            $totalOptimized += $batch['optimized'];
            $totalProcessed += $batch['processed'];
            $offset = $batch['next_offset'];

            \WP_CLI::log("Processed: {$totalProcessed}/{$batch['total']} | Optimized: {$totalOptimized}");
            if ($batch['done']) {
                break;
            }
        }

        \WP_CLI::success("Completed. Optimized: {$totalOptimized} images.");
    }

    /**
     * AJAX handler: Test single image optimization
     */
    public static function ajax_test_single_optimize()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied'], 403);
        }
        check_ajax_referer('mms_test_single_optimize', 'nonce');

        $attachment_id = isset($_POST['attachment_id']) ? (int) $_POST['attachment_id'] : 0;
        if (!$attachment_id) {
            wp_send_json_error(['message' => 'Invalid attachment ID'], 400);
        }

        $file = get_attached_file($attachment_id);
        if (!$file || !file_exists($file)) {
            wp_send_json_error(['message' => 'File not found'], 404);
        }

        $before_size = filesize($file);
        $optimized = self::optimize_attachment_by_id($attachment_id);
        $after_size = filesize($file);

        wp_send_json_success([
            'optimized' => $optimized,
            'file' => basename($file),
            'size' => size_format($after_size),
            'before_size' => size_format($before_size),
            'after_size' => size_format($after_size),
            'saved' => $before_size > $after_size ? size_format($before_size - $after_size) : '0 B',
        ]);
    }

    /**
     * AJAX handler: Restore image to original
     */
    public static function ajax_restore_image()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied'], 403);
        }
        check_ajax_referer('mms_restore_image', 'nonce');

        $attachment_id = isset($_POST['attachment_id']) ? (int) $_POST['attachment_id'] : 0;
        if (!$attachment_id) {
            wp_send_json_error(['message' => 'Invalid attachment ID'], 400);
        }

        $backupPath = get_post_meta($attachment_id, '_mms_backup_path', true);
        if (!$backupPath || !file_exists($backupPath)) {
            wp_send_json_error(['message' => 'Backup file not found'], 404);
        }

        $currentFile = get_attached_file($attachment_id);
        
        // Restore từ backup
        if (copy($backupPath, $currentFile)) {
            // Lấy size trước khi xóa backup
            $restoredSize = filesize($currentFile);
            $backupSize = get_post_meta($attachment_id, '_mms_backup_size', true);
            
            // Xóa file backup
            if (file_exists($backupPath)) {
                @unlink($backupPath);
            }
            
            // Xóa các meta data tối ưu và backup
            delete_post_meta($attachment_id, '_mms_optimized');
            delete_post_meta($attachment_id, '_mms_optimized_size');
            delete_post_meta($attachment_id, '_mms_saved_bytes');
            delete_post_meta($attachment_id, '_mms_backup_path');
            delete_post_meta($attachment_id, '_mms_backup_size');
            delete_post_meta($attachment_id, '_mms_original_size');
            
            // Force clear WordPress object cache for this attachment
            wp_cache_delete($attachment_id, 'post_meta');
            clean_post_cache($attachment_id);
            
            // Tạm thời remove hook mark_as_optimized để tránh đánh dấu lại khi regenerate metadata (nếu có)
            $has_hook = has_filter('wp_generate_attachment_metadata', [self::class, 'mark_as_optimized_on_upload']);
            if ($has_hook !== false) {
                remove_filter('wp_generate_attachment_metadata', [self::class, 'mark_as_optimized_on_upload'], 10);
            }
            
            // Regenerate metadata
            $meta = wp_generate_attachment_metadata($attachment_id, $currentFile);
            if (!is_wp_error($meta) && !empty($meta)) {
                wp_update_attachment_metadata($attachment_id, $meta);
            }
            
            // Re-add hook sau khi xong chỉ nếu đã remove trước đó
            if ($has_hook !== false) {
                add_filter('wp_generate_attachment_metadata', [self::class, 'mark_as_optimized_on_upload'], 10, 2);
            }
            
            // Xóa lại meta lần nữa để chắc chắn (phòng trường hợp hook vẫn chạy)
            delete_post_meta($attachment_id, '_mms_optimized');
            delete_post_meta($attachment_id, '_mms_optimized_size');
            delete_post_meta($attachment_id, '_mms_saved_bytes');
            
            // Clear cache lần cuối
            wp_cache_delete($attachment_id, 'post_meta');
            clean_post_cache($attachment_id);

            error_log("MMS Restore: Successfully restored image ID $attachment_id, size: " . size_format($restoredSize));
            error_log("MMS Restore: Cleared all meta and cache for ID $attachment_id");

            wp_send_json_success([
                'message' => 'Đã khôi phục ảnh về bản gốc',
                'size' => size_format($restoredSize),
                'is_optimized' => false,
                'has_backup' => false
            ]);
        } else {
            wp_send_json_error(['message' => 'Không thể khôi phục ảnh'], 500);
        }
    }

    /**
     * AJAX handler: Bulk restore images
     * Restore tất cả ảnh đã tối ưu về bản gốc
     */
    public static function ajax_bulk_restore_images()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied'], 403);
        }
        check_ajax_referer('mms_bulk_restore_images', 'nonce');

        $offset = isset($_POST['offset']) ? (int) $_POST['offset'] : 0;
        $limit = isset($_POST['limit']) ? (int) $_POST['limit'] : 50;

        // Get all images that have been optimized (have backup)
        $args = [
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'post_mime_type' => ['image/jpeg', 'image/png', 'image/webp'],
            'posts_per_page' => $limit,
            'offset' => $offset,
            'meta_query' => [
                [
                    'key' => '_mms_backup_path',
                    'compare' => 'EXISTS'
                ]
            ],
            'fields' => 'ids',
        ];

        $query = new \WP_Query($args);
        $total = $query->found_posts;
        $restored = 0;
        $failed = 0;

        foreach ($query->posts as $id) {
            $backupPath = get_post_meta($id, '_mms_backup_path', true);
            if (!$backupPath || !file_exists($backupPath)) {
                error_log("MMS Bulk Restore: Skipping ID $id - backup not found");
                $failed++;
                continue;
            }

            $currentFile = get_attached_file($id);
            
            // Restore from backup
            if (copy($backupPath, $currentFile)) {
                // Delete backup file
                @unlink($backupPath);
                
                // Delete all optimization meta
                delete_post_meta($id, '_mms_optimized');
                delete_post_meta($id, '_mms_optimized_size');
                delete_post_meta($id, '_mms_saved_bytes');
                delete_post_meta($id, '_mms_backup_path');
                delete_post_meta($id, '_mms_backup_size');
                delete_post_meta($id, '_mms_original_size');
                
                // Clear cache
                wp_cache_delete($id, 'post_meta');
                clean_post_cache($id);
                
                // Regenerate metadata (without marking as optimized)
                $has_hook = has_filter('wp_generate_attachment_metadata', [self::class, 'mark_as_optimized_on_upload']);
                if ($has_hook !== false) {
                    remove_filter('wp_generate_attachment_metadata', [self::class, 'mark_as_optimized_on_upload'], 10);
                }
                
                $meta = wp_generate_attachment_metadata($id, $currentFile);
                if (!is_wp_error($meta) && !empty($meta)) {
                    wp_update_attachment_metadata($id, $meta);
                }
                
                if ($has_hook !== false) {
                    add_filter('wp_generate_attachment_metadata', [self::class, 'mark_as_optimized_on_upload'], 10, 2);
                }
                
                // Delete meta again to be sure
                delete_post_meta($id, '_mms_optimized');
                delete_post_meta($id, '_mms_optimized_size');
                delete_post_meta($id, '_mms_saved_bytes');
                wp_cache_delete($id, 'post_meta');
                
                $restored++;
                error_log("MMS Bulk Restore: Restored ID $id");
            } else {
                $failed++;
                error_log("MMS Bulk Restore: Failed to restore ID $id");
            }
        }

        $nextOffset = $offset + $limit;
        $done = ($nextOffset >= $total);

        wp_send_json_success([
            'restored' => $restored,
            'failed' => $failed,
            'total' => $total,
            'next_offset' => $nextOffset,
            'done' => $done,
            'message' => sprintf('Đã restore %d/%d ảnh', $restored, $total)
        ]);
    }

    /**
     * AJAX handler: Get list of images for selection
     */
    public static function ajax_get_images_list()
    {
        error_log('MMS Debug: ajax_get_images_list called');
        
        if (!current_user_can('manage_options')) {
            error_log('MMS Debug: Permission denied');
            wp_send_json_error(['message' => 'Permission denied'], 403);
            return;
        }
        
        // Nonce check
        $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : '';
        if (!wp_verify_nonce($nonce, 'mms_get_images_list')) {
            error_log('MMS Debug: Invalid nonce: ' . $nonce);
            wp_send_json_error(['message' => 'Invalid nonce'], 403);
            return;
        }
        
        error_log('MMS Debug: Permission and nonce OK');

        $page = isset($_POST['page']) ? max(1, (int) $_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? max(1, min(100, (int) $_POST['per_page'])) : 20;
        $min_size_kb = isset($_POST['min_size_kb']) ? max(0, (int) $_POST['min_size_kb']) : 0;
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $sort_by = isset($_POST['sort_by']) ? sanitize_text_field($_POST['sort_by']) : 'date_desc';
        $unoptimized_only = isset($_POST['unoptimized_only']) ? filter_var($_POST['unoptimized_only'], FILTER_VALIDATE_BOOLEAN) : false;
        $debug_checkbox = isset($_POST['debug_checkbox']) ? filter_var($_POST['debug_checkbox'], FILTER_VALIDATE_BOOLEAN) : false;
        $debug_version = isset($_POST['debug_version']) ? $_POST['debug_version'] : 'unknown';

        error_log('MMS Debug: Filter params - search: ' . $search . ', min_size_kb: ' . $min_size_kb . ', sort_by: ' . $sort_by . ', unoptimized_only: ' . ($unoptimized_only ? 'true' : 'false'));
        error_log('MMS Debug: Debug params - debug_checkbox: ' . ($debug_checkbox ? 'true' : 'false') . ', debug_version: ' . $debug_version);
        error_log('MMS Debug: Raw POST unoptimized_only: ' . (isset($_POST['unoptimized_only']) ? $_POST['unoptimized_only'] : 'not set'));
        error_log('MMS Debug: All POST data: ' . print_r($_POST, true));
        
        // Debug: Check total images without filters
        $total_args = [
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'post_mime_type' => ['image/jpeg', 'image/png', 'image/webp'],
            'posts_per_page' => -1,
            'fields' => 'ids'
        ];
        $total_query = new \WP_Query($total_args);
        error_log('MMS Debug: Total images in database: ' . $total_query->found_posts);

        // Parse sort parameters
        $orderby = 'date';
        $order = 'DESC';
        $sort_by_size = false;
        
        switch ($sort_by) {
            case 'date_asc':
                $orderby = 'date';
                $order = 'ASC';
                break;
            case 'date_desc':
                $orderby = 'date';
                $order = 'DESC';
                break;
            case 'size_asc':
                $orderby = 'date'; // Fallback to date
                $order = 'DESC';
                $sort_by_size = 'ASC';
                break;
            case 'size_desc':
                $orderby = 'date'; // Fallback to date
                $order = 'DESC';
                $sort_by_size = 'DESC';
                break;
            case 'name_asc':
                $orderby = 'title';
                $order = 'ASC';
                break;
            case 'name_desc':
                $orderby = 'title';
                $order = 'DESC';
                break;
        }

        $args = [
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'post_mime_type' => ['image/jpeg', 'image/png', 'image/webp'],
            'posts_per_page' => $per_page,
            'paged' => $page,
            'orderby' => $orderby,
            'order' => $order,
        ];
        

        // Add search
        if (!empty($search)) {
            $args['s'] = $search;
        }

        // Build meta query
        $meta_query = [];

        // Add filter for optimization status
        if ($unoptimized_only) {
            $meta_query[] = [
                'key' => '_mms_optimized',
                'compare' => 'NOT EXISTS'
            ];
        }
        
        // Add meta query to args if we have any
        if (!empty($meta_query)) {
            $args['meta_query'] = $meta_query;
        }

        error_log('MMS Debug: WP_Query args: ' . print_r($args, true));
        
        $query = new \WP_Query($args);
        $images = [];
        
        error_log('MMS Debug: Query found ' . $query->found_posts . ' posts, returning ' . count($query->posts) . ' posts');
        error_log('MMS Debug: Processing ' . count($query->posts) . ' posts for images array');

        // Sort by size if needed
        if ($sort_by_size) {
            $posts_with_size = [];
            foreach ($query->posts as $post) {
                $file = get_attached_file($post->ID);
                $size = $file && file_exists($file) ? filesize($file) : 0;
                $posts_with_size[] = ['post' => $post, 'size' => $size];
            }
            
            // Sort by size
            usort($posts_with_size, function($a, $b) use ($sort_by_size) {
                if ($sort_by_size === 'ASC') {
                    return $a['size'] <=> $b['size'];
                } else {
                    return $b['size'] <=> $a['size'];
                }
            });
            
            $query->posts = array_column($posts_with_size, 'post');
        }

        foreach ($query->posts as $post) {
            $file = get_attached_file($post->ID);
            if (!$file || !file_exists($file)) {
                error_log("MMS Debug: Skipping ID {$post->ID} - file not found: " . $file);
                continue;
            }

            $size = filesize($file);
            $sizeKB = $size / 1024;

            // Skip if smaller than minimum
            if ($min_size_kb > 0 && $sizeKB < $min_size_kb) {
                error_log("MMS Debug: Skipping ID {$post->ID} - size {$sizeKB}KB < {$min_size_kb}KB");
                continue;
            }
            
            error_log("MMS Debug: Processing ID {$post->ID} - size {$sizeKB}KB");

            $isOptimized = (bool) get_post_meta($post->ID, '_mms_optimized', true);
            $hasBackup = (bool) get_post_meta($post->ID, '_mms_backup_path', true);
            $savedBytes = (int) get_post_meta($post->ID, '_mms_saved_bytes', true);

            $images[] = [
                'id' => $post->ID,
                'title' => get_the_title($post->ID) ?: basename($file),
                'url' => wp_get_attachment_url($post->ID),
                'thumb' => wp_get_attachment_image_url($post->ID, 'thumbnail'),
                'size' => size_format($size),
                'size_bytes' => $size,
                'size_kb' => round($sizeKB, 2),
                'is_optimized' => $isOptimized,
                'has_backup' => $hasBackup,
                'saved' => $savedBytes > 0 ? size_format($savedBytes) : null,
                'date' => get_the_date('Y-m-d H:i:s', $post->ID),
            ];
        }

        error_log('MMS Debug: Returning ' . count($images) . ' images from ' . $query->found_posts . ' total');
        error_log('MMS Debug: Final images array: ' . print_r(array_column($images, 'id'), true));
        
        wp_send_json_success([
            'images' => $images,
            'total' => (int) $query->found_posts,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => (int) $query->max_num_pages,
        ]);
    }

    /**
     * AJAX handler: Optimize selected images
     */
    public static function ajax_optimize_selected()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied'], 403);
        }
        check_ajax_referer('mms_optimize_selected', 'nonce');

        $ids = isset($_POST['ids']) ? array_map('intval', (array) $_POST['ids']) : [];
        if (empty($ids)) {
            wp_send_json_error(['message' => 'No images selected'], 400);
        }

        $results = [];
        $optimized = 0;
        $failed = 0;

        foreach ($ids as $id) {
            $before = filesize(get_attached_file($id));
            $success = self::optimize_attachment_by_id($id, false); // Force optimize
            $after = filesize(get_attached_file($id));

            if ($success) {
                $optimized++;
                $results[] = [
                    'id' => $id,
                    'success' => true,
                    'saved' => size_format($before - $after),
                ];
            } else {
                $failed++;
                $results[] = [
                    'id' => $id,
                    'success' => false,
                ];
            }
        }

        wp_send_json_success([
            'results' => $results,
            'total' => count($ids),
            'optimized' => $optimized,
            'failed' => $failed,
        ]);
    }

    /**
     * Đánh dấu ảnh đã được tối ưu sau khi upload thành công
     * Hook: wp_generate_attachment_metadata
     * 
     * @param array $metadata Attachment metadata
     * @param int $attachment_id Attachment ID
     * @return array Metadata (không thay đổi)
     */
    public static function mark_as_optimized_on_upload($metadata, $attachment_id)
    {
        // Chỉ đánh dấu nếu đã bật optimization
        if (!self::should_optimize()) {
            return $metadata;
        }

        $file = get_attached_file($attachment_id);
        if (!$file || !file_exists($file)) {
            return $metadata;
        }

        // Lấy kích thước file sau khi upload (đã qua compress)
        $afterSize = @filesize($file) ?: 0;

        // Đánh dấu đã tối ưu (vì đã qua compress_image_on_upload và compress_and_convert_to_webp)
        update_post_meta($attachment_id, '_mms_optimized', current_time('mysql'));
        update_post_meta($attachment_id, '_mms_optimized_size', $afterSize);
        
        // Không có backup vì đây là file mới upload
        // Không có saved_bytes vì không có bản gốc để so sánh
        
        error_log("MMS Optimize: Marked new upload as optimized - ID: $attachment_id, Size: " . size_format($afterSize));

        return $metadata;
    }

    /**
     * Nén và chuyển đổi hình ảnh sang WebP khi upload
     * 
     * @param array $upload Thông tin upload từ WordPress
     * @return array Thông tin upload đã được xử lý
     */
    public static function compress_and_convert_to_webp($upload)
    {
        $settings = self::get_settings();
        
        // CRITICAL: Chỉ chuyển đổi WebP nếu tính năng được BẬT
        if (!$settings['enable_webp_conversion']) {
            return $upload;
        }
        
        
        // Chỉ xử lý hình ảnh
        if (!isset($upload['type']) || strpos($upload['type'], 'image/') !== 0) {
            return $upload;
        }

        $image_path = $upload['file'];
        $image_info = getimagesize($image_path);
        
        if ($image_info === false) {
            return $upload;
        }

        // Chỉ xử lý JPG và PNG
        $supported_mime_types = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
        ];

        if (!array_key_exists($image_info['mime'], $supported_mime_types)) {
            return $upload;
        }

        // Kiểm tra hỗ trợ WebP
        if (!self::supports_webp()) {
            error_log('WebP conversion not supported on this server');
            return $upload;
        }

        try {
            // Đọc hình ảnh
            $image_data = file_get_contents($image_path);
            if (!$image_data) {
                return $upload;
            }

            $image = imagecreatefromstring($image_data);
            if (!$image) {
                return $upload;
            }

            // Kiểm tra ảnh truecolor (32-bit)
            if (!imageistruecolor($image)) {
                imagedestroy($image);
                return $upload;
            }

            // Resize nếu cần
            $width = imagesx($image);
            $height = imagesy($image);
            
            if ($width > $settings['max_width'] || $height > $settings['max_height']) {
                $ratio = min($settings['max_width'] / $width, $settings['max_height'] / $height);
                $new_width = intval($width * $ratio);
                $new_height = intval($height * $ratio);
                
                $resized_image = imagecreatetruecolor($new_width, $new_height);
                imagecopyresampled($resized_image, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                imagedestroy($image);
                $image = $resized_image;
            }
            
            // Tạo tên file WebP
            $webp_path = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $image_path);
            
            // Đảm bảo tên file duy nhất
            $upload_dir = wp_upload_dir();
            $file_dir = dirname($webp_path);
            $file_name = basename($webp_path);
            $unique_file_name = wp_unique_filename($upload_dir['path'], $file_name);
            $unique_webp_path = $file_dir . '/' . $unique_file_name;

            // Chuyển đổi sang WebP
            $webp_success = imagewebp($image, $unique_webp_path, $settings['webp_quality']);
            
            if ($webp_success) {
                // Kiểm tra kích thước file WebP
                $original_size = filesize($image_path);
                $webp_size = filesize($unique_webp_path);
                
                // Chỉ sử dụng WebP nếu kích thước nhỏ hơn theo cấu hình
                $min_saving = (100 - $settings['min_size_saving']) / 100;
                if ($webp_size < ($original_size * $min_saving)) {
                    // Xóa file gốc nếu không giữ
                    if (!$settings['preserve_original']) {
                        unlink($image_path);
                    }
                    
                    // Cập nhật thông tin upload
                    $upload['file'] = $unique_webp_path;
                    $upload['type'] = 'image/webp';
                    $upload['url'] = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $unique_webp_path);
                    
                    error_log("Successfully converted image to WebP: {$image_path} -> {$unique_webp_path} (Saved: " . round((($original_size - $webp_size) / $original_size) * 100, 2) . "%)");
                } else {
                    // Xóa file WebP vì không hiệu quả
                    unlink($unique_webp_path);
                    error_log("WebP conversion not beneficial for: {$image_path} (Original: {$original_size}, WebP: {$webp_size})");
                }
            } else {
                error_log("Failed to convert image to WebP: {$image_path}");
            }

            imagedestroy($image);
            
        } catch (Exception $e) {
            error_log("Error in WebP conversion: " . $e->getMessage());
        }

        return $upload;
    }

    /**
     * Nén hình ảnh JPG/PNG khi upload
     * 
     * @param array $file Thông tin file upload
     * @return array Thông tin file đã được nén
     */
    public static function compress_image_on_upload($file)
    {
        $settings = self::get_settings();
        
        // CRITICAL: Chỉ nén ảnh nếu tính năng được BẬT
        if (!$settings['enable_compression_image']) {
            return $file;
        }
        
        
        // Chỉ xử lý hình ảnh
        if (!isset($file['type']) || strpos($file['type'], 'image/') !== 0) {
            return $file;
        }

        $image_type = exif_imagetype($file['tmp_name']);
        
        // Chỉ xử lý JPG và PNG
        if ($image_type !== IMAGETYPE_JPEG && $image_type !== IMAGETYPE_PNG) {
            return $file;
        }

        try {
            $image = null;
            
            if ($image_type === IMAGETYPE_JPEG) {
                $image = imagecreatefromjpeg($file['tmp_name']);
                if ($image) {
                    // Nén JPG với chất lượng từ cấu hình
                    imagejpeg($image, $file['tmp_name'], $settings['jpg_quality']);
                }
            } elseif ($image_type === IMAGETYPE_PNG) {
                $image = imagecreatefrompng($file['tmp_name']);
                if ($image) {
                    // Kiểm tra ảnh truecolor
                    if (imageistruecolor($image)) {
                        // Nén PNG với mức nén từ cấu hình
                        imagepng($image, $file['tmp_name'], $settings['png_compression']);
                    }
                }
            }

            if ($image) {
                imagedestroy($image);
            }
            
        } catch (Exception $e) {
            error_log("Error in image compression: " . $e->getMessage());
        }

        return $file;
    }
}

