// eslint-disable-next-line no-unused-vars
import config from "@config";
import "@styles/login";
// import 'airbnb-browser-shims'; // Uncomment if needed

jQuery(document).ready(function () {
  jQuery("#login h1 a").attr("href", "https://mooms.dev/");
  jQuery("#login h1 a").attr("target", "_blank");
});

// ===== Migrate logic from resources/admin/js/login.js =====
document.addEventListener("DOMContentLoaded", function () {
  const userLogin = document.getElementById("user_login");
  const userPass = document.getElementById("user_pass");
  if (userLogin)
    userLogin.setAttribute("placeholder", "Username or Email Address");
  if (userPass) userPass.setAttribute("placeholder", "Password");

  // create div class welcome
  const welcomeDiv = document.createElement("div");
  welcomeDiv.className = "welcome";
  welcomeDiv.textContent = "Welcome administrators";

  // insert after logo
  const logo = document.querySelector("#login h1");
  if (logo) {
    logo.insertAdjacentElement("afterend", welcomeDiv);
  }

  const googleLoginBtn = document.getElementById("google-login-btn");
  if (googleLoginBtn) {
    googleLoginBtn.addEventListener("click", handleGoogleLogin);
  }
});

async function handleGoogleLogin(e) {
  e.preventDefault();

  const btn = e.target;
  const originalText = btn.innerHTML;

  // Set loading state
  btn.disabled = true;
  btn.innerHTML = "<span>Đang chuyển hướng...</span>";

  try {
    // Lấy redirect URL từ query string hoặc mặc định
    const urlParams = new URLSearchParams(window.location.search);
    const redirectTo = urlParams.get("redirect_to") || window.location.origin;

    // Gọi API để lấy Google OAuth URL
    const ajaxUrl =
      typeof ajax_object !== "undefined" && ajax_object.ajax_url
        ? ajax_object.ajax_url
        : "/wp-admin/admin-ajax.php";
    const response = await fetch(
      ajaxUrl +
        "?action=google_login&redirect_to=" +
        encodeURIComponent(redirectTo),
      {
        method: "GET",
        headers: { "X-Requested-With": "XMLHttpRequest" },
      }
    );
    const data = await response.json();

    if (data.success && data.data.redirect_url) {
      window.location.href = data.data.redirect_url;
    } else {
      showLoginError(
        (data.data && data.data.message) || "Có lỗi xảy ra khi đăng nhập Google"
      );
      resetButton(btn, originalText);
    }
  } catch (error) {
    console.error("Google login error:", error);
    showLoginError("Có lỗi xảy ra, vui lòng thử lại");
    resetButton(btn, originalText);
  }
}

function showLoginError(message) {
  let errorContainer = document.querySelector(".google-login-error");
  if (!errorContainer) {
    errorContainer = document.createElement("div");
    errorContainer.className = "google-login-error";
    errorContainer.style.cssText = `
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
            padding: 12px;
            margin: 10px 0;
            font-size: 14px;
            text-align: center;
        `;
    const googleContainer = document.querySelector(".google-login-container");
    if (googleContainer) googleContainer.appendChild(errorContainer);
  }
  errorContainer.textContent = message;
  errorContainer.style.display = "block";
  setTimeout(() => {
    if (errorContainer) errorContainer.style.display = "none";
  }, 5000);
}

function resetButton(btn, originalText) {
  btn.disabled = false;
  btn.innerHTML = originalText;
}

function handleGoogleCallback(data) {
  if (data.success) {
    showLoginSuccess(data.data.notification);
    setTimeout(() => {
      window.location.href = data.data.redirect;
    }, 1000);
  } else {
    showLoginError((data.data && data.data.message) || "Đăng nhập thất bại");
  }
}

function showLoginSuccess(notification) {
  const successContainer = document.createElement("div");
  successContainer.className = "google-login-success";
  successContainer.style.cssText = `
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
        border-radius: 4px;
        padding: 12px;
        margin: 10px 0;
        font-size: 14px;
        text-align: center;
    `;
  successContainer.innerHTML = `
        <strong>${notification.title}</strong><br>
        ${notification.message}
    `;
  const googleContainer = document.querySelector(".google-login-container");
  if (googleContainer) googleContainer.appendChild(successContainer);
  setTimeout(() => {
    if (successContainer.parentNode)
      successContainer.parentNode.removeChild(successContainer);
  }, 3000);
}

// Export global for popup callback use cases
window.handleGoogleCallback = handleGoogleCallback;
