<?php
/**
 * Theme Options.
 *
 * Here, you can register Theme Options using the Carbon Fields library.
 *
 * @link    https://carbonfields.net/docs/containers-theme-options/
 *
 * @package WPEmergeCli
 */

use Carbon_Fields\Container\Container;
use Carbon_Fields\Field\Field;

$optionsPage = Container::make('theme_options', __('MMS Theme', 'mms'))
	->set_page_file('app-theme-options.php')
	->set_page_menu_position(3)
	->add_tab(__('Branding | Thương hiệu', 'mms'), [
		Field::make('color', 'primary_color', __('Primary Color', 'mms'))
		->set_width(33.33)
		->set_default_value('#010101'),
		Field::make('color', 'secondary_color', __('Secondary Color', 'mms'))
		->set_width(33.33)
		->set_default_value('#626262'),
		Field::make('color', 'text_color', __('Text Color', 'mms'))
		->set_width(33.33)
		->set_default_value('#fff'),

		Field::make('image', 'logo', __('Logo', 'mms'))
			->set_width(33.33),
		Field::make('image', 'logo_dark', __('Logo Dark', 'mms'))
			->set_width(33.33),
		Field::make('image', 'default_image', __('Default image | Hình ảnh mặc định', 'mms'))
			->set_width(33.33),
	])

	->add_tab(__('Contact | Liên hệ', 'mms'), [
		Field::make('html', 'info', __('', 'mms'))
			->set_html('----<i> Information | Thông tin </i>----'),

		// Address
		Field::make('text', 'address' . currentLanguage(), __('', 'mms'))
			->set_attribute('placeholder', 'Address | Địa chỉ'),

		// Email
		Field::make('text', 'email', __('', 'mms'))
			->set_width(33.33)
			->set_attribute('placeholder', 'Email'),

		// Phone number
		Field::make('text', 'phone_number', __('', 'mms'))
			->set_width(33.33)
			->set_attribute('placeholder', 'Phone number | Số điện thoại'),

		// Working hours
		Field::make('text','working_hours', __('', 'mms'))
			->set_width(33.33)
			->set_attribute('placeholder', 'Working hours | Giờ làm việc'),

		//Slogan
		Field::make('text', 'slogan', __('', 'mms'))
			->set_width(33.33)
			->set_attribute('placeholder', 'Slogan | Slogan'),

		Field::make('html', 'socials', __('', 'mms'))
			->set_html('----<i> Socials | Mạng xã hội </i>----'),
		Field::make('image', 'icon_zalo', __('', 'mms'))->set_width(20),
		Field::make('text', 'zalo', __('', 'mms'))->set_width(30)
			->set_attribute('placeholder', 'zalo'),
		Field::make('image', 'icon_facebook', __('', 'mms'))->set_width(20),
		Field::make('text', 'facebook', __('', 'mms'))->set_width(30)
			->set_attribute('placeholder', 'facebook'),
		Field::make('image', 'icon_instagram', __('', 'mms'))->set_width(20),
		Field::make('text', 'instagram', __('', 'mms'))->set_width(30)
			->set_attribute('placeholder', 'instagram'),
		Field::make('image', 'icon_tiktok', __('', 'mms'))->set_width(20),
		Field::make('text', 'tiktok', __('', 'mms'))->set_width(30)
			->set_attribute('placeholder', 'tiktok'),
		Field::make('image', 'icon_youtube', __('', 'mms'))->set_width(20),
		Field::make('text', 'youtube', __('', 'mms'))->set_width(30)
			->set_attribute('placeholder', 'youtube'),
		Field::make('image', 'icon_linkedin', __('', 'mms'))->set_width(20),
		Field::make('text', 'linkedin', __('', 'mms'))->set_width(30)
			->set_attribute('placeholder', 'linkedin'),
		Field::make('image', 'icon_twitter', __('', 'mms'))->set_width(20),
		Field::make('text', 'twitter', __('', 'mms'))->set_width(30)
			->set_attribute('placeholder', 'twitter'),
	])

	->add_tab(__('Scripts', 'mms'), [
		Field::make('header_scripts', 'crb_header_script', __('Header Script', 'app')),
		Field::make('footer_scripts', 'crb_footer_script', __('Footer Script', 'app')),
	]);