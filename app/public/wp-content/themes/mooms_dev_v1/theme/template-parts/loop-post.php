<?php
global $post;
$postID = $post->ID;
$url = get_the_permalink($postID);
$thumbnail = getPostThumbnailUrl($postID);
$title = get_the_title($postID);
$excerpt = get_the_excerpt($postID);
$category = get_the_terms($postID, 'blog_cat');
?>

<div class="col-md-4 mt-5 loop-post">
	<a href="<?= $url; ?>">
		<div class="content-col">
			<div class="img">
				<img src="<?= $thumbnail; ?>" alt="<?= $title; ?>" loading="lazy">
				
				<div class="date">
					<?= get_the_date('Y.m.d', $postID); ?>
				</div>
			</div>

			<h3 class="heading"> <?= $title; ?> </h3>

			<?php
			if ($excerpt) {
				echo '<div class="desc"> ' . $excerpt . '</div>';
			}
			?>
		</div>
	</a>
</div>