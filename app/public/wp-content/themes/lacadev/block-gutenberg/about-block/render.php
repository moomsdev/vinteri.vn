<?php
/**
 * Render template for About Block (Dynamic)
 * 
 * @var array $attributes Block attributes
 * @var string $content Block content
 */

$block_id = isset($attributes['blockID']) ? $attributes['blockID'] : '';
$welcome_content = isset($attributes['welcomeContent']) ? $attributes['welcomeContent'] : '';
$about_image = isset($attributes['aboutImage']) ? $attributes['aboutImage'] : [];
$about_title = isset($attributes['aboutTitle']) ? $attributes['aboutTitle'] : '';
$about_desc = isset($attributes['aboutDesc']) ? $attributes['aboutDesc'] : '';

// Get image ID
$image_id = isset($about_image['id']) ? $about_image['id'] : 0;
?>

<section class="block-about" id="<?php echo esc_attr($block_id); ?>">
    <div class="block-about__head">
        <div class="scroll-circle">
            <svg viewBox="0 0 200 200">
                <path
                    id="circlePath"
                    d="M100,100 m-75,0 a75,75 0 1,1 150,0 a75,75 0 1,1 -150,0"
                    fill="none"
                />
                <text>
                    <textPath href="#circlePath" startOffset="0">
                        <?php echo esc_html($welcome_content); ?>
                    </textPath>
                </text>
            </svg>
            <div class="arrow"></div>
        </div>

        <?php if ($image_id || !empty($about_image['url'])) : ?>
            <div class="block-about__img">
                <figure>
                    <?php 
                    theResponsiveImage($image_id, 'mobile', [
                        'alt' => isset($about_image['alt']) && $about_image['alt'] ? $about_image['alt'] : esc_attr($about_title),
                        'loading' => 'lazy',
                        'fetchpriority' => 'high', // Ưu tiên tải nếu above the fold
                    ]); 
                    ?>
                </figure>
            </div>
        <?php endif; ?>
    </div>

    <div class="block-about__body">
        <h2 class="block-title text-center"><?php echo wp_kses_post($about_title); ?></h2>
        <div class="block-desc"><?php echo wp_kses_post($about_desc); ?></div>
    </div>
</section>
