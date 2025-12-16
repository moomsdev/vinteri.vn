import "@styles/admin";
import "@scripts/admin/custom_thumbnail_support.js";
import "@scripts/admin/bulk-optimize.js";
import Swal from "sweetalert2";
window.Swal = Swal;

// ===== Migrate logic from resources/admin/js/admin.js =====
let scripts = {
  frame: null,
  init: function () {
    this.frame = wp.media({
      title: "Select image",
      button: {
        text: "Use this image",
      },
      multiple: false,
    });
  },
  disableTheGrid: function () {
    jQuery("form#posts-filter").append(`
            <div class="gm-loader" style="position:absolute;z-index:99999999;top:0;left:0;right:0;bottom:0;display:flex;align-items:center;justify-content:center;background-color:rgba(192,192,192,0.51);color:#000000">
                Updating
            </div>
        `);
  },
  enableTheGrid: function () {
    jQuery("form#posts-filter").find(".gm-loader").remove();
  },
};

// Xử lý khi nhấn vào nút thay đổi ảnh đại diện bài viết
jQuery(document).on("click", "[data-trigger-change-thumbnail-id]", function () {
  let postId = jQuery(this).data("post-id");
  let thisButton = jQuery(this);

  let frame = wp.media({
    title: "Select image",
    button: {
      text: "Use this image",
    },
    multiple: false,
  });

  frame.on("select", function () {
    let attachment = frame.state().get("selection").first().toJSON();
    let attachmentId = attachment.id;
    let originalImageUrl = attachment.url || null;

    scripts.disableTheGrid();

    jQuery
      .post(
        "/wp-admin/admin-ajax.php",
        {
          action: "update_post_thumbnail_id",
          post_id: postId,
          attachment_id: attachmentId,
        },
        function (response) {
          if (response.success === true) {
            let imgElement = thisButton.find("img");

            if (imgElement.length) {
              // Nếu có ảnh, cập nhật src
              imgElement.attr("src", originalImageUrl);
            } else {
              // Nếu không có ảnh, thay thế text bằng ảnh mới
              thisButton
                .find(".no-image-text")
                .replaceWith(`<img src="${originalImageUrl}" alt="Thumbnail">`);
            }
          } else {
            alert(response.data.message);
          }
          scripts.enableTheGrid();
        }
      )
      .fail(function () {
        alert("Failed to update image.");
        scripts.enableTheGrid();
      });
  });

  frame.open();
});

// Khi trang tải, kiểm tra ảnh đại diện
jQuery(document).ready(function () {
  const postId = jQuery("input#post_ID").val();
  if (postId) {
    jQuery.post("/wp-admin/admin-ajax.php", {
      action: "mm_get_attachment_url_thumbnail",
      attachmentID: postId,
    });
  }
});

// Xử lý hiển thị/ẩn password (cho field có data-field="password-field")
document.addEventListener('DOMContentLoaded', function () {
  const passwordFields = document.querySelectorAll('input[data-field="password-field"]');
  if (!passwordFields || !passwordFields.length) return;

  passwordFields.forEach((passwordField) => {
    // Tránh gắn trùng khi hot-reload
    if (passwordField.parentNode.querySelector('[data-toggle="password-toggle"]')) return;

    const toggleButton = document.createElement('button');
    toggleButton.type = 'button';
    toggleButton.innerHTML = 'Show';
    toggleButton.style.marginLeft = '5px';
    toggleButton.style.cursor = 'pointer';
    toggleButton.setAttribute('data-toggle', 'password-toggle');
    passwordField.parentNode.appendChild(toggleButton);

    toggleButton.addEventListener('click', function() {
      if (passwordField.type === 'password') {
        passwordField.type = 'text';
        toggleButton.innerHTML = 'Hide';
      } else {
        passwordField.type = 'password';
        toggleButton.innerHTML = 'Show';
      }
    });
  });
});


// ===== Migrate logic from resources/scripts/admin/dashboard.js =====
(function($) {
    'use strict';

    const MMSDashboard = {
        init: function() {
            this.bindEvents();
            this.loadDashboardData();
            this.initTooltips();
        },
        bindEvents: function() {
            $(document).on('click', '.action-item', this.handleQuickAction);
            $(document).on('click', '.refresh-stats', this.refreshStats);
            $(document).on('click', '.health-item', this.showHealthDetails);
        },
        loadDashboardData: function() {
            if (!$('body').hasClass('index-php')) return;
            if (typeof mmsDashboard === 'undefined') return;
            $.ajax({
                url: mmsDashboard.ajaxurl,
                type: 'POST',
                data: { action: 'mms_get_dashboard_data', nonce: mmsDashboard.nonce },
                success: function(response) {
                    if (response && response.success) {
                        MMSDashboard.updateDashboardData(response.data);
                    }
                }
            });
        },
        updateDashboardData: function(data) {
            if (data.stats) this.updateStats(data.stats);
            if (data.activity) this.updateActivity(data.activity);
            if (data.health) this.updateHealth(data.health);
        },
        updateStats: function(stats) {
            Object.keys(stats).forEach(function(key) {
                const $statNumber = $('.stat-item').eq(Object.keys(stats).indexOf(key)).find('.stat-number');
                if ($statNumber.length) {
                    $statNumber.text(stats[key]);
                }
            });
        },
        updateActivity: function(activity) {
            // no-op for now
        },
        updateHealth: function(health) {
            // no-op for now
        },
        handleQuickAction: function(e) {
            const $this = $(this);
            const action = $this.data('action');
            if (!action) return;
            e.preventDefault();
            MMSDashboard.performQuickAction(action);
        },
        performQuickAction: function(action) {
            if (typeof mmsDashboard === 'undefined') return;
            $.ajax({
                url: mmsDashboard.ajaxurl,
                type: 'POST',
                data: { action: 'mms_quick_action', quick_action: action, nonce: mmsDashboard.nonce },
                beforeSend: function() { $('.action-item[data-action="' + action + '"]').addClass('mms-loading'); },
                complete: function() { $('.action-item[data-action="' + action + '"]').removeClass('mms-loading'); }
            });
        },
        refreshStats: function(e) {
            e.preventDefault();
            const $button = $(this);
            $button.addClass('mms-loading');
            MMSDashboard.loadDashboardData();
            setTimeout(function() { $button.removeClass('mms-loading'); }, 1000);
        },
        showHealthDetails: function(e) {
            const healthType = $(this).data('health-type');
            if (!healthType) return;
            e.preventDefault();
        },
        initTooltips: function() {
            $('.stat-item, .action-item, .health-item').each(function() {
                const $this = $(this);
                const title = $this.attr('title');
                if (title && typeof $this.tooltip === 'function') {
                    $this.tooltip({ position: { my: 'left+15 center', at: 'right center' }, tooltipClass: 'mms-tooltip' });
                }
            });
        }
    };

    $(document).ready(function() { MMSDashboard.init(); });
    window.MMSDashboard = MMSDashboard;

})(jQuery);
