<?php

namespace App\Settings;

class CustomLoginPage
{

    public function __construct()
    {
        $this->enqueueAssets();
        $this->addGoogleLoginButton();
        $this->addLoginLogoStyles();
    }

    /**
     * Enqueue CSS và JS cho trang login
     */
    private function enqueueAssets()
    {
        $my_theme   = wp_get_theme();
        $theme_name = str_replace('/theme', '', $my_theme->get_stylesheet());
        $theme_path = str_replace('wp-content/themes/'. $theme_name .'/theme', 'wp-content/themes/' . $theme_name . '/', $my_theme->get_template_directory_uri());

        add_action('login_enqueue_scripts', static function () use ($theme_path) {
            // wp_enqueue_style('custom-login', $theme_path . '/dist/styles/login.css');
            wp_enqueue_script('custom-login', $theme_path . '/dist/login.js', ['jquery'], '1.0.0', true);
            
            // Localize script với ajaxurl
            wp_localize_script('custom-login', 'ajax_object', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('google_login_nonce')
            ]);
        });
    }

    /**
     * Thêm nút đăng nhập Google vào trang login
     */
    private function addGoogleLoginButton()
    {
        add_action('login_form', [$this, 'addGoogleLoginButtonToForm']);
        add_action('register_form', [$this, 'addGoogleLoginButtonToForm']);
    }

    /**
     * Thêm CSS variables cho logo từ theme options
     */
    private function addLoginLogoStyles()
    {
        add_action('login_head', [$this, 'injectLoginLogoCSS']);
    }

    /**
     * Inject CSS variables với logo URL từ theme options
     */
    public function injectLoginLogoCSS()
    {
        // Lấy logo từ theme options
        $logo_id = carbon_get_theme_option('logo');
        $logo_url = '';
        
        if (!empty($logo_id)) {
            $logo_url = wp_get_attachment_image_url($logo_id, 'full');
        }
        
        // Fallback về logo mặc định nếu không có logo trong theme options
        if (empty($logo_url)) {
            $my_theme = wp_get_theme();
            $theme_name = str_replace('/theme', '', $my_theme->get_stylesheet());
            $theme_path = str_replace('wp-content/themes/'. $theme_name .'/theme', 'wp-content/themes/' . $theme_name . '/', $my_theme->get_template_directory_uri());
            $logo_url = $theme_path . '/resources/images/dev/moomsdev-white.png';
        }
        
        // Inject CSS variable
        echo '<style type="text/css">';
        echo ':root {';
        echo '  --login-logo-url: url("' . esc_url($logo_url) . '");';
        echo '}';
        echo '</style>';
    }

    /**
     * Hiển thị nút Google login trong form
     */
    public function addGoogleLoginButtonToForm()
    {
        // Chỉ hiển thị nếu đã cấu hình Google OAuth
        // Ưu tiên lấy từ Carbon Fields (key có tiền tố _), sau đó tới key không tiền tố, cuối cùng là constants
        $client_id = get_option('_google_client_id');
        if (empty($client_id)) {
            $client_id = get_option('_google_client_id');
        }
        if (empty($client_id) && defined('GOOGLE_CLIENT_ID')) {
            $client_id = GOOGLE_CLIENT_ID;
        }
        
        if (empty($client_id)) {
            return;
        }
        ?>
        <div class="google-login-container">
            <div class="google-login-divider">
                <span>hoặc</span>
            </div>
            <button type="button" id="google-login-btn" class="google-login-btn">
                <svg width="18" height="18" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg">
                    <g fill="#000" fill-rule="evenodd">
                        <path d="M9 3.48c1.69 0 2.83.73 3.48 1.34l2.54-2.48C13.46.89 11.43 0 9 0 5.48 0 2.44 2.02.96 4.96l2.91 2.26C4.6 5.05 6.62 3.48 9 3.48z" fill="#EA4335"/>
                        <path d="M17.64 9.2c0-.74-.06-1.28-.19-1.84H9v3.34h4.96c-.21 1.12-.84 2.08-1.79 2.71v2.26h2.9c1.7-1.57 2.68-3.88 2.68-6.47z" fill="#4285F4"/>
                        <path d="M3.88 10.78A5.54 5.54 0 0 1 3.58 9c0-.62.11-1.22.29-1.78L.96 4.96A9.008 9.008 0 0 0 0 9c0 1.45.35 2.82.96 4.04l2.92-2.26z" fill="#FBBC05"/>
                        <path d="M9 18c2.43 0 4.47-.8 5.96-2.18l-2.9-2.26c-.76.53-1.78.9-3.06.9-2.38 0-4.4-1.57-5.12-3.74L.97 13.04C2.45 15.98 5.48 18 9 18z" fill="#34A853"/>
                    </g>
                </svg>
                Đăng nhập với Google
            </button>
        </div>
        <?php
    }
}
