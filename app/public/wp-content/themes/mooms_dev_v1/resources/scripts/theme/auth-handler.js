/**
 * Auth Handler - Xử lý các API đăng nhập/đăng ký đã được chuẩn hóa JSON
 * Sử dụng với các API đã được cập nhật trong theme/setup/users/auth.php
 */

class AuthHandler {
    constructor() {
        this.init();
    }

    init() {
        this.bindEvents();
    }

    bindEvents() {
        // Đăng nhập
        const loginForm = document.getElementById('login-form');
        if (loginForm) {
            loginForm.addEventListener('submit', (e) => this.handleLogin(e));
        }

        // Đăng ký
        const registerForm = document.getElementById('register-form');
        if (registerForm) {
            registerForm.addEventListener('submit', (e) => this.handleRegister(e));
        }

        // Reset password
        const resetForm = document.getElementById('reset-password-form');
        if (resetForm) {
            resetForm.addEventListener('submit', (e) => this.handleResetPassword(e));
        }

        // Google login
        const googleLoginBtn = document.getElementById('google-login-btn');
        if (googleLoginBtn) {
            googleLoginBtn.addEventListener('click', (e) => this.handleGoogleLogin(e));
        }
    }

    /**
     * Xử lý đăng nhập
     */
    async handleLogin(e) {
        e.preventDefault();
        
        const form = e.target;
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        
        this.setLoading(submitBtn, true);

        try {
            const response = await fetch(ajaxurl, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                this.showNotification(data.data.notification, 'success');
                
                // Lưu thông tin user vào localStorage nếu cần
                if (data.data.user) {
                    localStorage.setItem('user_info', JSON.stringify(data.data.user));
                }
                
                // Redirect sau 1 giây
                setTimeout(() => {
                    window.location.href = data.data.redirect_to;
                }, 1000);
            } else {
                this.showNotification({
                    title: 'Lỗi',
                    message: data.data.message
                }, 'error');
            }
        } catch (error) {
            this.showNotification({
                title: 'Lỗi',
                message: 'Có lỗi xảy ra, vui lòng thử lại'
            }, 'error');
        } finally {
            this.setLoading(submitBtn, false);
        }
    }

    /**
     * Xử lý đăng ký
     */
    async handleRegister(e) {
        e.preventDefault();
        
        const form = e.target;
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        
        this.setLoading(submitBtn, true);

        try {
            const response = await fetch(ajaxurl, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                this.showNotification(data.data.notification, 'success');
                
                // Reset form
                form.reset();
                
                // Có thể chuyển sang form đăng nhập
                setTimeout(() => {
                    this.switchToLogin();
                }, 2000);
            } else {
                this.showNotification({
                    title: 'Lỗi',
                    message: data.data.message
                }, 'error');
            }
        } catch (error) {
            this.showNotification({
                title: 'Lỗi',
                message: 'Có lỗi xảy ra, vui lòng thử lại'
            }, 'error');
        } finally {
            this.setLoading(submitBtn, false);
        }
    }

    /**
     * Xử lý reset password
     */
    async handleResetPassword(e) {
        e.preventDefault();
        
        const form = e.target;
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        
        this.setLoading(submitBtn, true);

        try {
            const response = await fetch(ajaxurl, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                this.showNotification(data.data.notification, 'success');
                form.reset();
            } else {
                this.showNotification({
                    title: 'Lỗi',
                    message: data.data.message
                }, 'error');
            }
        } catch (error) {
            this.showNotification({
                title: 'Lỗi',
                message: 'Có lỗi xảy ra, vui lòng thử lại'
            }, 'error');
        } finally {
            this.setLoading(submitBtn, false);
        }
    }

    /**
     * Xử lý Google login
     */
    async handleGoogleLogin(e) {
        e.preventDefault();
        
        const btn = e.target;
        this.setLoading(btn, true);

        try {
            const response = await fetch(ajaxurl, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                // Redirect đến Google OAuth
                window.location.href = data.data.redirect_url;
            } else {
                this.showNotification({
                    title: 'Lỗi',
                    message: data.data.message
                }, 'error');
            }
        } catch (error) {
            this.showNotification({
                title: 'Lỗi',
                message: 'Có lỗi xảy ra, vui lòng thử lại'
            }, 'error');
        } finally {
            this.setLoading(btn, false);
        }
    }

    /**
     * Hiển thị thông báo
     */
    showNotification(notification, type = 'info') {
        // Tạo element thông báo
        const notificationEl = document.createElement('div');
        notificationEl.className = `notification notification-${type}`;
        notificationEl.innerHTML = `
            <div class="notification-content">
                <h4>${notification.title}</h4>
                <p>${notification.message}</p>
            </div>
            <button class="notification-close">&times;</button>
        `;

        // Thêm vào DOM
        document.body.appendChild(notificationEl);

        // Auto remove sau 5 giây
        setTimeout(() => {
            if (notificationEl.parentNode) {
                notificationEl.parentNode.removeChild(notificationEl);
            }
        }, 5000);

        // Xử lý nút đóng
        const closeBtn = notificationEl.querySelector('.notification-close');
        closeBtn.addEventListener('click', () => {
            if (notificationEl.parentNode) {
                notificationEl.parentNode.removeChild(notificationEl);
            }
        });
    }

    /**
     * Set trạng thái loading cho button
     */
    setLoading(button, isLoading) {
        if (isLoading) {
            button.disabled = true;
            button.dataset.originalText = button.textContent;
            button.textContent = 'Đang xử lý...';
        } else {
            button.disabled = false;
            button.textContent = button.dataset.originalText || button.textContent;
        }
    }

    /**
     * Chuyển sang form đăng nhập
     */
    switchToLogin() {
        const registerForm = document.getElementById('register-form');
        const loginForm = document.getElementById('login-form');
        
        if (registerForm && loginForm) {
            registerForm.style.display = 'none';
            loginForm.style.display = 'block';
        }
    }
}

// Khởi tạo khi DOM ready
document.addEventListener('DOMContentLoaded', () => {
    new AuthHandler();
});

// Export cho sử dụng global
window.AuthHandler = AuthHandler;
