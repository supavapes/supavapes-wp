<?php 
?>
<div class="checkout-box-container">
	<?php 
		if( have_rows('checkout_services','option') ):
			while( have_rows('checkout_services','option') ) : the_row();
				$service_icon = get_sub_field('service_icon','option');
				$service_title = get_sub_field('service_title','option');
		?>
		<div class="checkout-service-box">
			<div class="checkout-service-box-img">
				<img src="<?php echo esc_url($service_icon['url']);?>">
			</div>
			<h3 class="checkout-service-box-title"><?php echo esc_html($service_title); ?></h3>
		</div>
	<?php 
	endwhile;
	else :
	endif;
?>
</div>