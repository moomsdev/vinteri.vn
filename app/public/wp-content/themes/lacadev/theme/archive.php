<?php
/**
 * App Layout: layouts/app.php
 *
 * This is the template that is used for displaying all posts by default.
 *
 * @link    https://codex.wordpress.org/Template_Hierarchy
 *
 * @package WPEmergeTheme
 */

theBreadcrumb();
?>

<div class="archive-content">
	<div class="container">
		<div class="wrapper-content">
			<?php
			if (have_posts()):
				while (have_posts()):
					the_post();
					get_template_part('template-parts/loop', 'post');
				endwhile;
				wp_reset_postdata();
			endif;
			thePagination();
			?>
		</div>
	</div>
</div>