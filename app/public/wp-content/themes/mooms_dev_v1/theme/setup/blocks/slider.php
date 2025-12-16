<?php

use Carbon_Fields\Block;
use Carbon_Fields\Field;

Block::make(__('Block Slider', 'mms'))
    ->add_fields([
        Field::make('separator', 'slider_section', __('BLOCK SLIDER', 'mms'))->set_width(50),
        Field::make('media_gallery', 'img_slider', __('Chọn hình ảnh (khuyến nghị tỉ lệ 16/9)', 'mms')),
    ])
    ->set_render_callback(function ($fields, $attributes, $inner_blocks) {
        $sliders = !empty($fields['img_slider']) ? $fields['img_slider'] : '';
?>
    <section class="slider-block full-width">
        <div class="inner">
            <div class="swiper sliders">
                <div class="swiper-wrapper">
                    <?php
                    $i = 1;
                    foreach ($sliders as $slider) :
                    ?>
                        <div class="swiper-slide">
                            <figure class="responsive-media">
                                <img src="<?php echo esc_url(getImageUrlById($slider)); ?>" alt="slider-<?php echo $i; ?>">
                            </figure>
                        </div>
                    <?php
                        $i++;
                    endforeach;
                    ?>
                </div>
            </div>
        </div>
    </section>
<?php
    });
