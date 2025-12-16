<?php

/**
 * Theme footer partial.
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package WPEmergeTheme
 */
?>
<!-- footer -->
<footer>
</footer>
<!-- footer end -->

</div>
<!-- container-wrapper end -->

<!-- mobile menu -->
<!-- <nav id="mobile_menu">
	<?php
	// wp_nav_menu([
	// 	'menu' => 'main-menu',
	// 	'theme_location' => 'main-menu',
	// 	'container' => 'ul',
	// 	'menu_class' => '',
	// ])
	?>
</nav> -->

<!-- ScrolltoTop -->
<button 
    id="totop" 
    class="init" 
    aria-label="<?php esc_attr_e('Scroll to top', 'mms'); ?>" 
    title="<?php esc_attr_e('Back to top', 'mms'); ?>"
    type="button">
    <i class="fa fa-chevron-up" aria-hidden="true"></i>
</button>

<?php wp_footer(); ?>
</body>

</html>