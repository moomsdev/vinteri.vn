<?php
global $post;
$postID = $post->ID;
$thumbnail = getPostThumbnailUrl($postID);
$title = get_the_title($postID);
$desc = getPostMeta('blog_desc', $postID);
$price = getPostMeta('price', $postID);
?>

<div class="col-12 col-sm-6 loop-food mb-5">
	<div class="menu-wrap">
		<div class="menu-img">
			<img src="<?= $thumbnail; ?>" alt="<?= $title; ?>">
		</div>
		<div class="food-info">
			<h3 class="heading"><?= $title; ?></h3>
			<div class="desc"><?= $desc; ?></div>
			<div class="price"><?= $price; ?></div>
		</div>
	</div>
</div>