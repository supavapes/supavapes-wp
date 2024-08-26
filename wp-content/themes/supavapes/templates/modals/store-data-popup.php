<?php
$terms     = get_the_terms( get_the_ID(), 'store_locator' );
$term_data = array();

foreach ( $terms as $term ) {
	$term_data[] = array(
		'name' => $term->name,
		'address_line_1' => get_term_meta($term->term_id, 'address_line_1', true),
		'address_line_2' => get_term_meta($term->term_id, 'address_line_2', true),
		'contact_number' => get_term_meta($term->term_id, 'contact_number', true)
	);
}

$store_count_class = "";
if(count($term_data) === 1){
	$store_count_class = "one-store-locations";
}else if(count($term_data) === 2){
	$store_count_class = "two-store-locations";
}else if(count($term_data) >= 3){
	$store_count_class = "multiple-store-locations";
}
?>
<div class="store-popup">
	<div class="overlay"></div>
	<div class="store-popup-content <?php echo esc_attr($store_count_class); ?>">
		<span class="store-popup-close"><img src="/wp-content/uploads/2024/06/close.png"></span>
		<div class="store-popup-content-box">
		<div class="store-popup-content-detail">
		</div>
		</div>       
	</div>
</div>