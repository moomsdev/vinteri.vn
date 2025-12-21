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

$optionsPage = Container::make('theme_options', __('Laca Theme', 'laca'))
	->set_page_file('app-theme-options.php')
	->set_page_menu_position(3)
	->add_tab(__('Branding | Thương hiệu', 'laca'), [
		Field::make('color', 'primary_color', __('Primary Color', 'laca'))
		->set_width(33.33)
		->set_default_value('#010101'),
		Field::make('color', 'secondary_color', __('Secondary Color', 'laca'))
		->set_width(33.33)
		->set_default_value('#626262'),
		Field::make('color', 'text_color', __('Text Color', 'laca'))
		->set_width(33.33)
		->set_default_value('#fff'),

		Field::make('image', 'logo' . currentLanguage(), __('Logo', 'laca'))
			->set_width(33.33),
		Field::make('image', 'default_image' . currentLanguage(), __('Default image | Hình ảnh mặc định', 'laca'))
			->set_width(33.33),
	])

	->add_tab(__('Contact | Liên hệ', 'laca'), [
		Field::make('html', 'info', __('', 'laca'))
			->set_html('----<i> Information | Thông tin </i>----'),
		Field::make('text', 'company' . currentLanguage(), __('', 'laca'))->set_width(50)
			->set_attribute('placeholder', 'Company | Công ty'),

		// Address
		Field::make('text', 'address' . currentLanguage(), __('', 'laca'))
			->set_attribute('placeholder', 'Address | Địa chỉ'),

		// Email
		Field::make('text', 'email' . currentLanguage(), __('', 'laca'))
			->set_width(33.33)
			->set_attribute('placeholder', 'Email'),

		// Phone number
		Field::make('text', 'phone_number' . currentLanguage(), __('', 'laca'))
			->set_width(33.33)
			->set_attribute('placeholder', 'Phone number | Số điện thoại'),

		// Working hours
		Field::make('text','working_hours' . currentLanguage(), __('', 'laca'))
			->set_width(33.33)
			->set_attribute('placeholder', 'Working hours | Giờ làm việc'),

		//Slogan
		Field::make('text', 'slogan' . currentLanguage(), __('', 'laca'))
			->set_width(33.33)
			->set_attribute('placeholder', 'Slogan | Slogan'),

		Field::make('html', 'socials', __('', 'laca'))
			->set_html('----<i> Socials | Mạng xã hội </i>----'),
		Field::make('image', 'icon_zalo', __('', 'laca'))->set_width(20),
		Field::make('text', 'zalo', __('', 'laca'))->set_width(30)
			->set_attribute('placeholder', 'zalo'),
		Field::make('image', 'icon_facebook', __('', 'laca'))->set_width(20),
		Field::make('text', 'facebook', __('', 'laca'))->set_width(30)
			->set_attribute('placeholder', 'facebook'),
		Field::make('image', 'icon_instagram', __('', 'laca'))->set_width(20),
		Field::make('text', 'instagram', __('', 'laca'))->set_width(30)
			->set_attribute('placeholder', 'instagram'),
		Field::make('image', 'icon_tiktok', __('', 'laca'))->set_width(20),
		Field::make('text', 'tiktok', __('', 'laca'))->set_width(30)
			->set_attribute('placeholder', 'tiktok'),
		Field::make('image', 'icon_youtube', __('', 'laca'))->set_width(20),
		Field::make('text', 'youtube', __('', 'laca'))->set_width(30)
			->set_attribute('placeholder', 'youtube'),
		Field::make('image', 'icon_linkedin', __('', 'laca'))->set_width(20),
		Field::make('text', 'linkedin', __('', 'laca'))->set_width(30)
			->set_attribute('placeholder', 'linkedin'),
		Field::make('image', 'icon_twitter', __('', 'laca'))->set_width(20),
		Field::make('text', 'twitter', __('', 'laca'))->set_width(30)
			->set_attribute('placeholder', 'twitter'),
	])

	->add_tab(__('Scripts', 'laca'), [
		Field::make('header_scripts', 'crb_header_script', __('Header Script', 'laca')),
		Field::make('footer_scripts', 'crb_footer_script', __('Footer Script', 'laca')),
	]);
