<?php

use Overtrue\Socialite\SocialiteManager;

add_action('wp_ajax_nopriv_user_login', 'mm_user_login');
add_action('wp_ajax_user_login', 'mm_user_login');

define('SOCIAL_DRIVER', [
    'google'   => [
        'client_id'     => (get_option('_google_client_id') ?: get_option('_google_client_id') ?: (defined('GOOGLE_CLIENT_ID') ? GOOGLE_CLIENT_ID : '')),
        'client_secret' => (get_option('_google_client_secret') ?: get_option('_google_client_secret') ?: (defined('GOOGLE_CLIENT_SECRET') ? GOOGLE_CLIENT_SECRET : '')),
        'redirect'      => (get_option('_google_redirect_uri') ?: get_option('_google_redirect_uri') ?: (defined('GOOGLE_REDIRECT_URI') ? GOOGLE_REDIRECT_URI : '')),
    ],
]);
function mm_user_login()
{
    if (empty($_POST)) {
        wp_send_json_error([
            'message' => __('Dữ liệu không hợp lệ', 'mms')
        ], 400);
    }

    if (!wp_verify_nonce($_POST['_token'], 'user_dang_nhap')) {
        wp_send_json_error([
            'message' => __('Token không hợp lệ', 'mms')
        ], 403);
    }

    if (empty($_POST['user_login']) || empty($_POST['password'])) {
        wp_send_json_error([
            'message' => __('Tài khoản hoặc mật khẩu không đúng, vui lòng kiểm tra lại', 'mms')
        ], 400);
    }

    $user = wp_signon([
        'user_login'    => sanitize_user($_POST['user_login']),
        'user_password' => $_POST['password'], // wp_signon handles hashing
        'remember'      => true,
    ], false);

    if (is_wp_error($user)) {
        wp_send_json_error([
            'message' => $user->get_error_message()
        ], 401);
    }

    wp_send_json_success([
        'message' => __('Đăng nhập thành công', 'mms'),
        'user' => [
            'id' => $user->ID,
            'email' => $user->user_email,
            'display_name' => $user->display_name
        ],
        'redirect_to' => !empty($_POST['redirect_to']) ? esc_url($_POST['redirect_to']) : home_url('/'),
        'notification' => [
            'title' => __('Xin chào, ', 'mms') . $user->user_email,
            'message' => __('Chúc mừng bạn đã đăng nhập thành công', 'mms')
        ]
    ]);
}

add_action('wp_ajax_nopriv_user_register', 'mm_user_register');
add_action('wp_ajax_user_register', 'mm_user_register');
function mm_user_register()
{
    if (empty($_POST)) {
        wp_send_json_error([
            'message' => __('Dữ liệu không hợp lệ', 'mms')
        ], 400);
    }

    /* Kiem tra captcha */
    //    $captcha = $_POST['g-recaptcha-response'];
    //    if (empty($captcha)) {
    //        wp_send_json_error([
    //            'message' => __("Bạn chưa nhập mã xác nhận (chọn vào I'm not robot)", 'mms')
    //        ], 400);
    //    }
    //    $response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=6LfIuzYUAAAAADoy5KWNcnYkDumOexP1apz9Vv3v&response=" . $captcha . "&remoteip=" . $_SERVER['REMOTE_ADDR']);
    //    $response = json_decode($response, true);
    //    if (!$response['success']) {
    //        wp_send_json_error([
    //            'message' => __("Mã xác nhận chưa chính xác", 'mms')
    //        ], 400);
    //    }

    /* Kiem tra token truoc khi xu ly */
    if (!wp_verify_nonce($_REQUEST['_token'], 'user_dang_ky_thanh_vien')) {
        wp_send_json_error([
            'message' => __('Token không hợp lệ', 'mms')
        ], 403);
    }

    if (empty($_POST['first_name'])) {
        wp_send_json_error([
            'message' => __('Vui lòng nhập họ', 'mms')
        ], 400);
    }

    if (empty($_POST['last_name'])) {
        wp_send_json_error([
            'message' => __('Vui lòng nhập tên', 'mms')
        ], 400);
    }

    if (empty($_POST['email']) || !is_email($_POST['email'])) {
        wp_send_json_error([
            'message' => __('Vui lòng nhập email hợp lệ', 'mms')
        ], 400);
    }

    if (empty($_POST['password'])) {
        wp_send_json_error([
            'message' => __('Vui lòng nhập mật khẩu', 'mms')
        ], 400);
    }

    if ($_POST['password'] !== $_POST['password_confirmation']) {
        wp_send_json_error([
            'message' => __('Vui lòng kiểm tra lại mật khẩu', 'mms')
        ], 400);
    }

    $userParams = [
        'user_login'   => sanitize_user($_POST['user_login']),
        'user_email'   => sanitize_email($_POST['email']),
        'user_pass'    => $_POST['password_confirmation'],
        'display_name' => sanitize_text_field($_POST['last_name']),
    ];

    $idUser = wp_insert_user($userParams);

    if (is_wp_error($idUser)) {
        wp_send_json_error([
            'message' => $idUser->get_error_message()
        ], 400);
    }

    // Cập nhật user meta
    if (!empty($_POST['birthday'])) {
        update_user_meta($idUser, '_user_birthday', sanitize_text_field($_POST['birthday']));
    }
    if (!empty($_POST['sex'])) {
        update_user_meta($idUser, '_user_gender', sanitize_text_field($_POST['sex']));
    }

    wp_send_json_success([
        'message' => __('Đăng ký thành công', 'mms'),
        'user' => [
            'id' => $idUser,
            'email' => sanitize_email($_POST['email']),
            'display_name' => sanitize_text_field($_POST['last_name'])
        ],
        'notification' => [
            'title' => __('Chúc mừng!', 'mms'),
            'message' => __('Tài khoản của bạn đã được tạo thành công', 'mms')
        ]
    ]);
}

add_action('wp_ajax_nopriv_user_reset_password', 'mm_user_reset_password');
add_action('wp_ajax_user_reset_password', 'mm_user_reset_password');
function mm_user_reset_password()
{
    if (empty($_POST)) {
        wp_send_json_error([
            'message' => __('Dữ liệu không hợp lệ', 'mms')
        ], 400);
    }

    if (!wp_verify_nonce($_POST['_token'], 'user_reset_password')) {
        wp_send_json_error([
            'message' => __('Token không hợp lệ', 'mms')
        ], 403);
    }

    if (empty($_POST['user_login']) || !is_email($_POST['user_login'])) {
        wp_send_json_error([
            'message' => __('Vui lòng nhập email hợp lệ', 'mms')
        ], 400);
    }

    $user = get_user_by('email', sanitize_email($_POST['user_login']));
    if (!$user) {
        wp_send_json_error([
            'message' => __('Email không tồn tại trong hệ thống', 'mms')
        ], 404);
    }

    // Gửi email reset password
    $reset_key = get_password_reset_key($user);
    if (is_wp_error($reset_key)) {
        wp_send_json_error([
            'message' => __('Không thể tạo mã reset password', 'mms')
        ], 500);
    }

    $reset_url = network_site_url("wp-login.php?action=rp&key=$reset_key&login=" . rawurlencode($user->user_login), 'login');
    
    // Gửi email (có thể tùy chỉnh template email)
    $subject = __('Reset mật khẩu', 'mms');
    $message = sprintf(
        __('Bạn đã yêu cầu reset mật khẩu. Vui lòng click vào link sau để đặt lại mật khẩu: %s', 'mms'),
        $reset_url
    );
    
    $sent = wp_mail($user->user_email, $subject, $message);
    
    if (!$sent) {
        wp_send_json_error([
            'message' => __('Không thể gửi email reset password', 'mms')
        ], 500);
    }

    wp_send_json_success([
        'message' => __('Email reset password đã được gửi. Vui lòng kiểm tra hộp thư', 'mms'),
        'notification' => [
            'title' => __('Thành công', 'mms'),
            'message' => __('Email reset password đã được gửi', 'mms')
        ]
    ]);
}

add_action('wp_ajax_nopriv_google_login', 'googleLogin');
add_action('wp_ajax_google_login', 'googleLogin');
function googleLogin() {
    if (is_user_logged_in()) {
        wp_send_json_success([
            'message' => __('Bạn đã đăng nhập rồi', 'mms'),
            'user' => [
                'id' => get_current_user_id(),
                'email' => wp_get_current_user()->user_email,
                'display_name' => wp_get_current_user()->display_name
            ],
            'redirect_to' => home_url('/')
        ]);
    }

    // Kiểm tra config Google OAuth
    $config = SOCIAL_DRIVER;
    if (empty($config['google']['client_id']) || empty($config['google']['client_secret'])) {
        wp_send_json_error([
            'message' => __('Google OAuth chưa được cấu hình đúng', 'mms')
        ], 500);
    }

    // Không override redirect URI, giữ nguyên cấu hình
    // $redirect = !empty($_GET['redirect_to']) ? esc_url($_GET['redirect_to']) : home_url('/');
    
    try {
        // Sử dụng callback URL cụ thể
        $config['google']['redirect'] = home_url('/wp-login.php?action=google_callback');
        
        $socialite = new SocialiteManager($config);
        $response = $socialite->driver('google')->redirect();
        
        // Nếu response là object, lấy URL
        $redirect_url = is_string($response) ? $response : $response->getTargetUrl();
        
        wp_send_json_success([
            'message' => __('Chuyển hướng đến Google', 'mms'),
            'redirect_url' => $redirect_url
        ]);
    } catch (Exception $e) {
        error_log('Google Login Error: ' . $e->getMessage());
        wp_send_json_error([
            'message' => __('Lỗi khi tạo Google OAuth URL: ' . $e->getMessage(), 'mms')
        ], 500);
    }
}

function socialCallbackRedirectUrl()
{
    $user = wp_get_current_user();

    // Trả về JSON cho popup callback
    wp_send_json_success([
        'success' => true,
        'notification' => [
            'title' => __('Xin chào, ', 'mms') . $user->user_email,
            'message' => __('Chúc mừng bạn đã đăng nhập thành công', 'mms')
        ],
        'redirect' => home_url('/'),
        'user' => [
            'id' => $user->ID,
            'email' => $user->user_email,
            'display_name' => $user->display_name
        ]
    ]);
}

add_action('wp_ajax_nopriv_google_admin_callback', 'googleAdminCallback');
add_action('wp_ajax_google_admin_callback', 'googleAdminCallback');
/**
 * Xử lý callback đăng nhập/đăng ký admin bằng Google
 */
function googleAdminCallback() {
    $socialite = new SocialiteManager(SOCIAL_DRIVER);
    $user = $socialite->driver('google')->user();

    if (!$user || empty($user->getEmail())) {
        wp_send_json_error([
            'message' => __('Không lấy được thông tin từ Google!', 'mms')
        ], 400);
    }

    // Kiểm tra email có phải admin không
    $admin_user = get_user_by('email', $user->getEmail());
    if ($admin_user && in_array('administrator', $admin_user->roles)) {
        // Đăng nhập user admin
        wp_set_current_user($admin_user->ID);
        wp_set_auth_cookie($admin_user->ID);

        wp_send_json_success([
            'success' => true,
            'notification' => [
                'title' => __('Xin chào, ', 'mms') . $admin_user->user_email,
                'message' => __('Chúc mừng bạn đã đăng nhập thành công với quyền admin', 'mms')
            ],
            'redirect' => admin_url('/'),
            'user' => [
                'id' => $admin_user->ID,
                'email' => $admin_user->user_email,
                'display_name' => $admin_user->display_name,
                'roles' => $admin_user->roles
            ]
        ]);
    } else {
        wp_send_json_error([
            'message' => __('Tài khoản Google này không có quyền admin!', 'mms')
        ], 403);
    }
}

/**
 * Xử lý callback từ Google OAuth cho trang login
 */
add_action('init', 'handleGoogleOAuthCallback');
function handleGoogleOAuthCallback() {
    // Chỉ xử lý khi có code từ Google
    if (!isset($_GET['code']) || !isset($_GET['state'])) {
        return;
    }
    
    // Kiểm tra xem có đang ở trang login không
    global $pagenow;
    
    // Kiểm tra cả $pagenow và URL hiện tại
    $current_url = $_SERVER['REQUEST_URI'] ?? '';
    $is_login_page = ($pagenow === 'wp-login.php') || (strpos($current_url, '/wp-login.php') !== false);
    
    if (!$is_login_page) {
        return;
    }
    
    // Kiểm tra action để đảm bảo đây là callback từ Google
    if (!isset($_GET['action']) || $_GET['action'] !== 'google_callback') {
        return;
    }
    
    // Kiểm tra state để đảm bảo request hợp lệ
    $state = sanitize_text_field($_GET['state']);
    if (empty($state)) {
        wp_redirect(wp_login_url() . '?error=invalid_state');
        exit;
    }
    
    try {
        // Log callback start
        error_log('Google OAuth Callback Start - Code: ' . $_GET['code'] . ', State: ' . $_GET['state']);
        
        $socialite = new SocialiteManager(SOCIAL_DRIVER);
        $googleUser = $socialite->driver('google')->user();
        
        // Log user data
        error_log('Google OAuth User Data - Email: ' . $googleUser->getEmail() . ', Name: ' . $googleUser->getName());
        
        if (!$googleUser || empty($googleUser->getEmail())) {
            wp_redirect(wp_login_url() . '?error=google_auth_failed');
            exit;
        }
        
        $email = $googleUser->getEmail();
        $name = $googleUser->getName();
        $googleId = $googleUser->getId();
        
        // Tìm user hiện có
        $user = get_user_by('email', $email);
        
        if (!$user) {
            // Tạo user mới nếu chưa tồn tại (subscriber) nhưng yêu cầu admin duyệt trước khi cho login
            $username = sanitize_user($googleUser->getNickname() ?: explode('@', $email)[0]);
            $username = function_exists('wp_unique_username') ? wp_unique_username($username) : wp_unique_id($username . '_');

            $user_id = wp_insert_user([
                'user_login' => $username,
                'user_email' => $email,
                'display_name' => $name,
                'user_pass' => wp_generate_password(),
                'role' => 'subscriber'
            ]);

            if (is_wp_error($user_id)) {
                wp_redirect(wp_login_url() . '?error=user_creation_failed');
                exit;
            }

            // Đánh dấu chờ duyệt
            update_user_meta($user_id, '_google_pending_approval', 'yes');

            // Gửi thông báo cho admin (tùy chọn)
            $admin_email = get_option('admin_email');
            if ($admin_email) {
                @wp_mail($admin_email, __('Yêu cầu duyệt tài khoản mới', 'mms'), sprintf(__('Người dùng %s (%s) đăng ký qua Google. Vào admin để cấp quyền.', 'mms'), $name, $email));
            }

            // Không đăng nhập ngay, chuyển về trang login với thông báo
            wp_redirect(wp_login_url() . '?error=pending_approval');
            exit;
        }

        // Nếu user tồn tại nhưng đang chờ duyệt thì không cho đăng nhập
        if (get_user_meta($user->ID, '_google_pending_approval', true) === 'yes') {
            wp_redirect(wp_login_url() . '?error=pending_approval');
            exit;
        }

        // Lưu Google ID vào user meta
        update_user_meta($user->ID, '_google_id', $googleId);
        update_user_meta($user->ID, '_google_avatar', $googleUser->getAvatar());

        // Đăng nhập user (đã được duyệt)
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, true);
        
        // Đảm bảo user được set đúng (hook wp_login sẽ tự động set flag _show_admin_welcome)
        do_action('wp_login', $user->user_login, $user);
        
        // Redirect về trang đích - xử lý theo role
        $redirect_to = !empty($_GET['redirect_to']) ? esc_url($_GET['redirect_to']) : home_url('/');
        
        // Nếu user có quyền admin, redirect vào admin
        if (current_user_can('manage_options')) {
            $redirect_to = !empty($_GET['redirect_to']) ? esc_url($_GET['redirect_to']) : admin_url();
        }
        
        // Log để debug
        error_log('Google OAuth Success - User ID: ' . $user->ID . ', Role: ' . implode(', ', $user->roles) . ', Redirect to: ' . $redirect_to);
        
        // Clear any output buffer
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        // Redirect với status code 302
        wp_redirect($redirect_to, 302);
        exit;
        
    } catch (Exception $e) {
        error_log('Google OAuth Error: ' . $e->getMessage());
        error_log('Google OAuth Error File: ' . $e->getFile() . ' Line: ' . $e->getLine());
        error_log('Google OAuth Error Trace: ' . $e->getTraceAsString());
        wp_redirect(wp_login_url() . '?error=oauth_exception');
        exit;
    }
}

/**
 * Thêm thông báo lỗi vào trang login
 */
add_action('login_errors', 'showGoogleLoginErrors');
function showGoogleLoginErrors($errors) {
    if (isset($_GET['error'])) {
        $error_code = sanitize_text_field($_GET['error']);
        
        switch ($error_code) {
            case 'pending_approval':
                $errors .= '<br>' . __('Tài khoản của bạn đang chờ quản trị viên duyệt. Vui lòng thử lại sau.', 'mms');
                break;
            case 'invalid_state':
                $errors .= '<br>' . __('Lỗi xác thực Google. Vui lòng thử lại.', 'mms');
                break;
            case 'google_auth_failed':
                $errors .= '<br>' . __('Không thể lấy thông tin từ Google. Vui lòng thử lại.', 'mms');
                break;
            case 'user_creation_failed':
                $errors .= '<br>' . __('Không thể tạo tài khoản. Vui lòng liên hệ quản trị viên.', 'mms');
                break;
            case 'oauth_exception':
                $errors .= '<br>' . __('Có lỗi xảy ra khi đăng nhập Google. Vui lòng thử lại.', 'mms');
                break;
            default:
                $errors .= '<br>' . __('Có lỗi xảy ra. Vui lòng thử lại.', 'mms');
        }
    }
    
    return $errors;
}

/**
 * Admin: Hiển thị trạng thái duyệt tài khoản và checkbox duyệt trên trang hồ sơ user
 */
add_action('show_user_profile', 'mms_google_pending_approval_field');
add_action('edit_user_profile', 'mms_google_pending_approval_field');
function mms_google_pending_approval_field($user)
{
    if (!current_user_can('manage_options')) {
        return;
    }

    $pending = get_user_meta($user->ID, '_google_pending_approval', true) === 'yes';
    $approved = !$pending;
    ?>
    <h3><?php _e('Google Account Approval', 'mms'); ?></h3>
    <table class="form-table" role="presentation">
        <tr>
            <th><label for="mms_google_approved"><?php _e('Trạng thái', 'mms'); ?></label></th>
            <td>
                <label style="display: inline-flex; align-items: center; gap: 10px;">
                    <input 
                        type="checkbox" 
                        id="mms_google_approved" 
                        name="mms_google_approved" 
                        value="1" 
                        <?php checked($approved, true); ?>
                        data-user-id="<?php echo esc_attr($user->ID); ?>"
                        data-nonce="<?php echo wp_create_nonce('mms_approve_user_' . $user->ID); ?>"
                    >
                    <span id="mms_approval_status" style="font-weight: 600;">
                        <?php if ($approved): ?>
                            <span style="color: #198754;"><?php _e('Đã duyệt', 'mms'); ?></span>
                        <?php else: ?>
                            <span style="color: #d63638;"><?php _e('Chờ duyệt', 'mms'); ?></span>
                        <?php endif; ?>
                    </span>
                </label>
            </td>
        </tr>
    </table>
    <script>
    jQuery(document).ready(function($) {
        $('#mms_google_approved').on('change', function() {
            const checkbox = $(this);
            const userId = checkbox.data('user-id');
            const nonce = checkbox.data('nonce');
            const isChecked = checkbox.is(':checked');
            const statusSpan = $('#mms_approval_status');
            
            // Disable checkbox while processing
            checkbox.prop('disabled', true);
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'mms_toggle_user_approval',
                    user_id: userId,
                    approve: isChecked ? '1' : '0',
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        if (isChecked) {
                            statusSpan.html('<span style="color: #198754;"><?php _e('Đã duyệt', 'mms'); ?></span>');
                        } else {
                            statusSpan.html('<span style="color: #d63638;"><?php _e('Chờ duyệt', 'mms'); ?></span>');
                        }
                    } else {
                        alert(response.data || '<?php _e('Có lỗi xảy ra', 'mms'); ?>');
                        // Revert checkbox state
                        checkbox.prop('checked', !isChecked);
                    }
                },
                error: function() {
                    alert('<?php _e('Có lỗi xảy ra', 'mms'); ?>');
                    // Revert checkbox state
                    checkbox.prop('checked', !isChecked);
                },
                complete: function() {
                    checkbox.prop('disabled', false);
                }
            });
        });
    });
    </script>
    <?php
}

/**
 * AJAX Handler: Toggle user approval status
 */
add_action('wp_ajax_mms_toggle_user_approval', 'mms_toggle_user_approval');
function mms_toggle_user_approval()
{
    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('Bạn không có quyền thực hiện thao tác này.', 'mms'));
    }

    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $approve = isset($_POST['approve']) && $_POST['approve'] === '1';
    $nonce = $_POST['nonce'] ?? '';

    if (!$user_id || !wp_verify_nonce($nonce, 'mms_approve_user_' . $user_id)) {
        wp_send_json_error(__('Yêu cầu không hợp lệ.', 'mms'));
    }

    $user = get_user_by('id', $user_id);
    if (!$user) {
        wp_send_json_error(__('User không tồn tại.', 'mms'));
    }

    if ($approve) {
        // Duyệt tài khoản: xóa meta pending
        delete_user_meta($user_id, '_google_pending_approval');
        
        // Gửi email thông báo cho user
        @wp_mail(
            $user->user_email, 
            __('Tài khoản đã được duyệt', 'mms'), 
            __('Tài khoản của bạn đã được quản trị viên duyệt. Bạn có thể đăng nhập bằng Google ngay bây giờ.', 'mms')
        );
        
        wp_send_json_success(__('Đã duyệt tài khoản thành công.', 'mms'));
    } else {
        // Bỏ duyệt: gắn lại meta pending
        update_user_meta($user_id, '_google_pending_approval', 'yes');
        
        wp_send_json_success(__('Đã chuyển về trạng thái chờ duyệt.', 'mms'));
    }
}