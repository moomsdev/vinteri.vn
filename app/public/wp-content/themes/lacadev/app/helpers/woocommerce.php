<?php

add_filter('woocommerce_empty_price_html', fn() => __('<span>Liên hệ</span>', 'laca'));

/**
 * Ajax thêm vào giỏ hàng
 */
add_action('wp_ajax_nopriv_gm_add_to_cart', 'ajaxAddToCart');
add_action('wp_ajax_gm_add_to_cart', 'ajaxAddToCart');
function ajaxAddToCart()
{
	$product_id = apply_filters('woocommerce_add_to_cart_product_id', absint($_POST['product_id']));
	$product = wc_get_product($product_id);
	$quantity = empty($_POST['quantity']) ? 1 : apply_filters('woocommerce_stock_amount', $_POST['quantity']);
	$variation_id = $_POST['variation_id'] ?? 0;
	$variation = $_POST['variation'] ?? [];

	if (wc()->cart->add_to_cart($product_id, $quantity, $variation_id, $variation)) {
		WC_AJAX::get_refreshed_fragments();
		wp_send_json_success();
	} else {
		wp_send_json_error(__('Thêm vào giỏ thất bại', 'laca'));
	}
}

/**
 * Lấy danh mục sản phẩm gốc
 */
function getRootProductCategories()
{
	return get_terms([
		'taxonomy'   => 'product_cat',
		'hide_empty' => false,
		'parent'     => 0,
	]);
}

/**
 * Lấy sản phẩm theo danh mục
 */
function getProductsByCategory(WP_Term $productCat, $productCount = null)
{
	$productCount = $productCount ?: get_option('posts_per_page');
	return new WP_Query([
		'post_type'      => 'product',
		'posts_per_page' => $productCount,
		'tax_query'      => [[
			'taxonomy' => 'product_cat',
			'field'    => 'term_id',
			'terms'    => $productCat->term_id,
		]],
	]);
}

/**
 * Lấy sản phẩm nổi bật
 */
function getFeaturedProducts($productCount = null)
{
	$productCount = $productCount ?: get_option('posts_per_page');
	return new WP_Query([
		'post_type'      => 'product',
		'posts_per_page' => $productCount,
		'tax_query'      => [[
			'taxonomy' => 'product_visibility',
			'field'    => 'name',
			'terms'    => 'featured',
		]],
	]);
}

/**
 * Lấy sản phẩm giảm giá
 */
function getIsOnSaleProducts($productCount = null)
{
	$productCount = $productCount ?: get_option('posts_per_page');
	return new WP_Query([
		'post_type'      => 'product',
		'posts_per_page' => $productCount,
		'meta_query'     => [
			'relation' => 'OR',
			[
				'key'     => '_sale_price',
				'value'   => 0,
				'compare' => '>',
			],
			[
				'key'     => '_min_variation_sale_price',
				'value'   => 0,
				'compare' => '>',
			],
		],
	]);
}

/**
 * Lấy sản phẩm bán chạy
 */
function getBestSellingProducts($productCount = null)
{
	$productCount = $productCount ?: get_option('posts_per_page');
	return new WP_Query([
		'post_type'      => 'product',
		'posts_per_page' => $productCount,
		'meta_key'       => 'total_sales',
		'orderby'        => 'meta_value_num',
	]);
}

/**
 * Lấy sản phẩm đánh giá cao
 */
function getTopRatingProducts($productCount = null)
{
	$productCount = $productCount ?: get_option('posts_per_page');
	return new WP_Query([
		'post_type'      => 'product',
		'posts_per_page' => $productCount,
		'meta_key'       => '_wc_average_rating',
		'orderby'        => 'meta_value_num',
	]);
}

/**
 * Lấy phần trăm giảm giá của sản phẩm
 */
function getProductPercentageSaleOff(WC_Product $product)
{
	if (!$product->is_on_sale()) return 0;

	$regular_price = $product->get_regular_price();
	$sale_price = $product->get_sale_price();
	$percentage = 0;

	if ($regular_price && $sale_price) {
		$percentage = ($regular_price - $sale_price) / $regular_price * 100;
	} elseif ($product->is_type('variable')) {
		foreach ($product->get_children() as $child_id) {
			$variation = wc_get_product($child_id);
			if ($variation) {
				$percentage = max($percentage, ($variation->get_regular_price() - $variation->get_sale_price()) / $variation->get_regular_price() * 100);
			}
		}
	}

	return round($percentage);
}

function theProductPercentageSaleOff()
{
	global $product;
	$percent = getProductPercentageSaleOff($product);
	if ($percent) {
		echo "<span class=\"product__percent-sale-off\">{$percent}%</span>";
	}
}

/**
 * Lấy giá sản phẩm
 */
function getProductPrice(WC_Product $product)
{
	if ($product->is_type('variable')) {
		return $product->get_variation_price('min');
	}

	$regular_price = $product->get_regular_price();
	$sale_price = $product->get_sale_price();

	if ($sale_price) {
		echo "<div class='price-product'>
                <span class='price regular-price'>" . number_format($regular_price, 0, ',', '.') . " VND</span>
                <span class='price sale-price'>" . number_format($sale_price, 0, ',', '.') . " VND</span>
            </div>";
	} else {
		echo "<div class='price-product'>
                <span class='price regular-price'>" . number_format($regular_price, 0, ',', '.') . " VND</span>
            </div>";
	}
}

function theProductPrice()
{
	global $product;
	getProductPrice($product);
}
