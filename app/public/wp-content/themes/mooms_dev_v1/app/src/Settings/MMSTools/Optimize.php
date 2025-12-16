<?php

namespace App\Settings\MMSTools;

class Optimize
{
    protected $currentUser;

	protected $superUsers = SUPER_USER;

	protected $errorMessage = '';

	public function __construct()
	{
		$this->currentUser = wp_get_current_user();

		// Disable unnecessary items
		if (get_option('_disable_use_jquery_migrate') === 'yes') {
			$this->disableUseJqueryMigrate();
		}

		if (get_option('_disable_gutenberg_css') === 'yes') {
			$this->disableGutenbergCss();
		}

		if (get_option('_disable_classic_css') === 'yes') {
			$this->disableClassicCss();
		}

		if (get_option('_disable_emoji') === 'yes') {
			$this->disableEmoji();
		}

		// Optimization Library
		if (get_option('_enable_instant_page') === 'yes') {
			$this->enableInstantPage();
		}

		if (get_option('_enable_smooth_scroll') === 'yes') {
			$this->enableSmoothScroll();
		}

		// The function of lazy loading images
		if (get_option('_enable_lazy_loading_images') === 'yes') {
			$this->enableLazyLoadingImages();
		}

		if (get_option('_enable_advanced_resource_hints') === 'yes') {
			$this->addAdvancedResourceHints();
		}

		if (get_option('_enable_optimize_images') === 'yes') {
			add_filter('wp_get_attachment_image_attributes', [$this, 'optimizeImages'], 10, 3);
		}	

		if (get_option('_enable_optimize_content_images') === 'yes') {
			add_filter('the_content', [$this, 'optimizeContentImages'], 10, 1);
		}

		if (get_option('_enable_register_service_worker') === 'yes') {
			$this->registerServiceWorker();
		}
	}

	public function disableUseJqueryMigrate()
	{
		add_action('wp_default_scripts', function ($scripts) {
			if (!is_admin() && isset($scripts->registered['jquery'])) {
				$script = $scripts->registered['jquery'];
				if ($script->deps) {
					$script->deps = array_diff($script->deps, ['jquery-migrate']);
				}
			}
		});
	}

	public function disableGutenbergCss()
	{
		add_action('wp_enqueue_scripts', [$this, 'enqueueScriptsCallback']);
	}

	public function disableClassicCss()
	{
		// Handled in enqueueScriptsCallback
	}

	public function disableEmoji()
	{
		add_action('init', function () {
			remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
			remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
			remove_action( 'wp_print_styles', 'print_emoji_styles' );
			remove_action( 'admin_print_styles', 'print_emoji_styles' );	
			remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
			remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );	
			remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );	
		});
	}

	public function enableInstantPage()
	{
		// Handled in enqueueScriptsCallback
	}

	public function enableSmoothScroll()
	{
		// Handled in enqueueScriptsCallback
	}
	
	/**
	 * Consolidated wp_enqueue_scripts callback
	 * Combines all enqueue operations into one hook to improve performance
	 */
	public function enqueueScriptsCallback()
	{
		// Disable Gutenberg CSS (from disableGutenbergCss)
		if (is_front_page() && get_option('_disable_gutenberg_css') === 'yes') {
			wp_dequeue_style( 'wp-block-library' );
			wp_dequeue_style( 'wp-block-library-theme' );
			wp_dequeue_style( 'wc-blocks-style' );
		}
		
		// Disable Classic CSS (from disableClassicCss)
		if (is_front_page() && get_option('_disable_classic_css') === 'yes') {
			wp_dequeue_style( 'classic-theme-styles' );
		}
		
		// Enable InstantPage (from enableInstantPage)
		if (get_option('_enable_instant_page') === 'yes') {
			wp_enqueue_script('instantpage', get_stylesheet_directory_uri() . '/../resources/admin/lib/instantpage.js', array(), '5.7.0', true);
		}
		
		// Enable Smooth Scroll (from enableSmoothScroll)
		if (get_option('_enable_smooth_scroll') === 'yes') {
			wp_enqueue_script('smooth-scroll', get_stylesheet_directory_uri() . '/../resources/admin/lib/smooth-scroll.min.js', array(), '1.4.16', true);
		}
	}

	public function enableLazyLoadingImages()
	{
		if (!is_admin()) {
			wp_add_inline_script('jquery', '
				jQuery(document).ready(function($) {
					$("img").addClass("lazyload").each(function() {
						var dataSrc = $(this).attr("src");
						$(this).attr("data-src", dataSrc).removeAttr("src");
					});
				});
			');
			wp_enqueue_script( 'lazyload', get_stylesheet_directory_uri() . '/../resources/admin/lib/lazysizes.min.js', array('jquery'), '5.3.2', true);
		}
	}

    /**
     * Thêm resource hint (preload, preconnect, ...)
     */
    public function addAdvancedResourceHints()
    {
        add_action('wp_head', function () {
            // Có thể thêm các resource hint tại đây
        }, 1);
    }

    /**
     * Tối ưu hóa thuộc tính ảnh (lazy loading, alt, dimension)
     */
    public function optimizeImages($attr, $attachment, $size)
    {
        $attr['loading'] = 'lazy';
        $attr['decoding'] = 'async';
        
        // Add alt text if missing (critical for SEO and accessibility)
        if (empty($attr['alt'])) {
            $post_title = get_the_title($attachment->ID);
            $attr['alt'] = !empty($post_title) ? esc_attr($post_title) : esc_attr__('Image', 'mms');
        }
        
        // Add dimensions if missing
        if (empty($attr['width']) || empty($attr['height'])) {
            $image_meta = wp_get_attachment_metadata($attachment->ID);
            if (!empty($image_meta['width']) && !empty($image_meta['height'])) {
                $attr['width'] = $image_meta['width'];
                $attr['height'] = $image_meta['height'];
            }
        }
        return $attr;
    }

    /**
     * Lazy load ảnh trong nội dung bài viết
     */
    public function optimizeContentImages($content)
    {
        $content = preg_replace('/<img((?![^>]*loading)[^>]*)>/', '<img$1 loading="lazy" decoding="async">', $content);
        $content = preg_replace_callback('/<img([^>]+)>/', function ($matches) {
            $img_tag = $matches[0];
            if (strpos($img_tag, 'srcset') === false) {
                return $img_tag;
            }
            return $img_tag;
        }, $content);
        return $content;
    }

    /**
     * Đăng ký service worker cho cache
     */
    public function registerServiceWorker()
    {
        if (!is_admin() && !is_user_logged_in()) {
            ?>
            <script>
                if ('serviceWorker' in navigator && !navigator.serviceWorker.controller) {
                    window.addEventListener('load', function () {
                        navigator.serviceWorker.register('<?= get_template_directory_uri(); ?>/dist/sw.js', {
                            scope: '/'
                        }).then(function (registration) {
                            console.log('SW registered:', registration.scope);
                        }).catch(function (error) {
                            console.log('SW registration failed:', error);
                        });
                    });
                }
            </script>
            <?php
        }
    }
} 