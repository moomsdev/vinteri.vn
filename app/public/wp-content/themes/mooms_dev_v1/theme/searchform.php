<?php
/**
 * Search form partial.
 *
 * @link https://codex.wordpress.org/Styling_Theme_Forms#The_Search_Form
 *
 * @package WPEmergeTheme
 */

?>
<form action="<?php echo esc_url( home_url( '/' ) ); ?>" class="search-form" method="get" role="search">
	<div class="search-form__input-wrapper">
		<label for="s" class="screen-reader-text"><?php esc_html_e('Search for:', 'app'); ?></label>
		<input type="text" title="<?php esc_attr_e('Search for:', 'app'); ?>" name="s" value="" id="s" placeholder="<?php esc_attr_e('Search &hellip;', 'app'); ?>" class="search-form__field" />
	</div>

	<input type="submit" value="<?php esc_attr_e( 'Search', 'app' ); ?>" class="search-form__submit-button screen-reader-text" />
</form>
