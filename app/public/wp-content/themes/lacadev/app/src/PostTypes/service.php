<?php

namespace App\PostTypes;

use Carbon_Fields\Container\Container;
use Carbon_Fields\Field;

class service extends \App\Abstracts\AbstractPostType
{

    public function __construct()
    {
        $this->showThumbnailOnList = true;
        $this->supports            = [
            'title',
            'editor',
            'thumbnail',
            'excerpt',
            'page-attributes',
        ];

        $this->menuIcon         = 'dashicons-admin-generic';
        // $this->menuIcon = get_template_directory_uri() . '/images/custom-icon.png';
        $this->post_type        = 'service';
        $this->singularName     = $this->pluralName = __('Service', 'laca');
        $this->titlePlaceHolder = __('Service', 'laca');
        $this->slug             = 'services';
        parent::__construct();
    }

    public function metaFields()
    {
        // Container::make('post_meta', __('Information | Thông tin', 'laca'))
        //     ->set_context('carbon_fields_after_title')
        //     ->set_priority('high')
        //     ->where('post_type', 'IN', [$this->post_type])
        //     ->add_fields([
        //         Field::make('text', 'price', __('Price | Giá', 'laca'))->set_width(30),
        //         Field::make('textarea', 'food_desc', __('Description | Mô tả', 'laca'))->set_width(70),
        //     ]);
    }
}
