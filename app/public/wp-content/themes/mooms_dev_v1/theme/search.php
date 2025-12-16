<?php
/**
 * App Layout: layouts/app.php
 *
 * This is the template that is used for displaying all pages by default.
 *
 * @link https://codex.wordpress.org/Template_Hierarchy
 *
 * @package WPEmergeTheme
 */

?>
<div class="page-listing journal">
    <div class="container">
		<?php
		theBreadcrumb();
		?>
		<?php
		global $wp_query; // Lấy đối tượng truy vấn tìm kiếm hiện tại
		$total_results = $wp_query->found_posts; // Số lượng bài viết trong kết quả tìm kiếm
		$title = sprintf(__('%s', 'mms'), get_search_query());
		if ( is_search() ) :
			echo '<h1 class="title-single text-center my-5 text-uppercase"> Tìm kiếm </h1>';
			echo '<h4 class="title-result text-center mb-5"> '. $total_results .' '. __('kết quả cho từ khóa', 'mms') .' "'. $title .'" </h4>';
		endif;
		?>

        <div class="row gy-5">
        <?php
        if (have_posts()) :
            while(have_posts()) : the_post();
				get_template_part('template-parts/loop','post');
            endwhile;
            wp_reset_postdata();
        else:
            echo '<h3> '. __('Không có bài viết liên quan tới từ khóa tìm kiếm', 'mms') .' </h3>';
        endif;
        thePagination();
        ?>
    </div>

    </div>
</section>
</div>
