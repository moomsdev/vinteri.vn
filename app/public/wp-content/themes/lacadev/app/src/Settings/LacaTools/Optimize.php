<?php

namespace App\Settings\LacaTools;

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

		if (get_option('_enable_instant_page') === 'yes') {
			$this->enableInstantPage();
		}

		if (get_option('_enable_smooth_scroll') === 'yes') {
			$this->enableSmoothScroll();
		}

		if (get_option('_enable_lazy_loading_images') === 'yes') {
			$this->enableLazyLoadingImages();
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
		add_action('wp_enqueue_scripts', function () {
			if ( is_front_page() ) {
				wp_dequeue_style( 'wp-block-library' );
				wp_dequeue_style( 'wp-block-library-theme' );
				wp_dequeue_style( 'wc-blocks-style' );
			}
		});
	}

	public function disableClassicCss()
	{
		add_action('wp_enqueue_scripts', function () {
			if ( is_front_page() ) {
				wp_dequeue_style( 'classic-theme-styles' );
			}
		});
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
		add_action('wp_enqueue_scripts', function () {
			wp_enqueue_script('instantpage', get_template_directory_uri() . '/dist/instantpage.js', array(), '5.7.0', true);
		});
	}

	public function enableSmoothScroll()
	{
		add_action('wp_enqueue_scripts', function () {
			wp_enqueue_script('smooth-scroll', get_template_directory_uri() . '/dist/smooth-scroll.min.js', array(), '1.4.16', true);
		});
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
			wp_enqueue_script('lazyload', get_template_directory_uri() . '/dist/lazysizes.min.js', array('jquery'), '5.3.2', true);
		}
	}

    /**
     * Tối ưu hóa thuộc tính ảnh (lazy loading, alt, dimension)
     */
    public function optimizeImages($attr, $attachment, $size)
    {
        $attr['loading'] = 'lazy';
        $attr['decoding'] = 'async';
        if (empty($attr['alt'])) {
            $attr['alt'] = get_the_title($attachment->ID) ?: 'Image';
        }
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
            $sw_path = get_template_directory() . '/dist/sw.js';
            
            // Only register if SW file exists
            if (!file_exists($sw_path)) {
                return;
            }
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