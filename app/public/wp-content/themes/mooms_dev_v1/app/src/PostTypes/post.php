<?php

namespace App\PostTypes;

use Carbon_Fields\Container\Container;
use Carbon_Fields\Field;
use mms\Abstracts\AbstractPostType;

class post extends \App\Abstracts\AbstractPostType
{

    /**
     * Document: https://docs.carbonfields.net/#/containers/post-meta
     */
    public function metaFields()
    {
        // Container::make('post_meta', __('Description | Mô tả', 'mms'))
        //     ->set_context('normal') // Sử dụng context 'normal' để hiển thị trong Gutenberg
        //     ->set_priority('default')
        //     ->where('post_type', 'IN', [$this->post_type])
        //     ->add_fields([
        //         Field::make('complex', 'post_extra', __('', 'mms'))
        //             ->set_layout('tabbed-horizontal')
        //             ->set_width(70)
        //             ->add_fields([
        //                 Field::make('text', 'content', __('Content | Nội dung', 'mms')),
        //             ])->set_header_template('<% if (content) { %><%- content %><% } %>'),
        //     ]);
    }
}
