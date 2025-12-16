<?php

namespace App\Settings\MMSTools;

/**
 * Security Headers Management
 * Thêm các HTTP security headers để bảo vệ website
 */
class SecurityHeaders
{
    protected $currentUser;
    protected $superUsers = SUPER_USER;

    public function __construct()
    {
        $this->currentUser = wp_get_current_user();
        
        // Add security headers
        add_action('send_headers', [$this, 'addSecurityHeaders']);
    }

    /**
     * Thêm các security headers chính
     */
    public function addSecurityHeaders()
    {
        // Chỉ thêm headers cho frontend, không admin
        if (is_admin()) {
            return;
        }

        // X-Frame-Options: Chống clickjacking
        if (get_option('_enable_x_frame_options') === 'yes') {
            header('X-Frame-Options: SAMEORIGIN');
        }

        // X-Content-Type-Options: Ngăn MIME type sniffing
        if (get_option('_enable_x_content_type_options') === 'yes') {
            header('X-Content-Type-Options: nosniff');
        }

        // Referrer-Policy: Kiểm soát referrer information
        if (get_option('_enable_referrer_policy') === 'yes') {
            $policy = get_option('_referrer_policy_value', 'strict-origin-when-cross-origin');
            header("Referrer-Policy: {$policy}");
        }

        // Strict-Transport-Security (HSTS): Bắt buộc HTTPS
        if (get_option('_enable_hsts') === 'yes' && is_ssl()) {
            $max_age = get_option('_hsts_max_age', '31536000'); // 1 year
            $include_subdomains = get_option('_hsts_include_subdomains') === 'yes' ? '; includeSubDomains' : '';
            $preload = get_option('_hsts_preload') === 'yes' ? '; preload' : '';
            header("Strict-Transport-Security: max-age={$max_age}{$include_subdomains}{$preload}");
        }

        // Content-Security-Policy
        if (get_option('_enable_csp') === 'yes') {
            $this->addContentSecurityPolicy();
        }

        // Permissions-Policy (thay thế Feature-Policy)
        if (get_option('_enable_permissions_policy') === 'yes') {
            $this->addPermissionsPolicy();
        }
    }

    /**
     * Content Security Policy
     */
    private function addContentSecurityPolicy()
    {
        // Đọc CSP mode: report-only hoặc enforce
        $mode = get_option('_csp_mode', 'enforce');
        $header_name = $mode === 'report-only' ? 'Content-Security-Policy-Report-Only' : 'Content-Security-Policy';

        // Lấy danh sách domains được phép từ options
        $allowed_domains_raw = get_option('_csp_allowed_domains', '');
        $allowed_domains = array_filter(array_map('trim', explode("\n", $allowed_domains_raw)));

        // Build CSP directives
        $directives = [];

        // default-src
        $directives[] = "default-src 'self'";

        // script-src
        $script_sources = ["'self'"];
        if (get_option('_csp_allow_inline_scripts') === 'yes') {
            $script_sources[] = "'unsafe-inline'";
        }
        if (get_option('_csp_allow_eval') === 'yes') {
            $script_sources[] = "'unsafe-eval'";
        }
        foreach ($allowed_domains as $domain) {
            $script_sources[] = "https://{$domain}";
        }
        $directives[] = "script-src " . implode(' ', $script_sources);

        // style-src
        $style_sources = ["'self'"];
        if (get_option('_csp_allow_inline_styles') === 'yes') {
            $style_sources[] = "'unsafe-inline'";
        }
        foreach ($allowed_domains as $domain) {
            $style_sources[] = "https://{$domain}";
        }
        $directives[] = "style-src " . implode(' ', $style_sources);

        // img-src
        $directives[] = "img-src 'self' data: https:";

        // font-src
        $font_sources = ["'self'"];
        $font_sources[] = "https://fonts.gstatic.com";
        foreach ($allowed_domains as $domain) {
            if (strpos($domain, 'font') !== false) {
                $font_sources[] = "https://{$domain}";
            }
        }
        $directives[] = "font-src " . implode(' ', $font_sources);

        // connect-src (AJAX, fetch, WebSocket)
        $directives[] = "connect-src 'self'";

        // frame-ancestors
        $directives[] = "frame-ancestors 'self'";

        // Report URI (nếu có)
        $report_uri = get_option('_csp_report_uri', '');
        if (!empty($report_uri)) {
            $directives[] = "report-uri {$report_uri}";
        }

        $csp = implode('; ', $directives);
        header("{$header_name}: {$csp}");
    }

    /**
     * Permissions-Policy (thay thế Feature-Policy)
     */
    private function addPermissionsPolicy()
    {
        $policies = [];

        // Helper to build directive based on yes/no
        $build = static function ($feature, $enabled) {
            return $enabled === 'yes' ? sprintf('%s=(self)', $feature) : sprintf('%s=()', $feature);
        };

        $policies[] = $build('camera', get_option('_permissions_camera'));
        $policies[] = $build('microphone', get_option('_permissions_microphone'));
        $policies[] = $build('geolocation', get_option('_permissions_geolocation'));
        $policies[] = $build('payment', get_option('_permissions_payment'));
        $policies[] = $build('usb', get_option('_permissions_usb'));
        $policies[] = 'fullscreen=(self)';
        $policies[] = $build('autoplay', get_option('_permissions_autoplay'));

        header('Permissions-Policy: ' . implode(', ', $policies));
    }
}

