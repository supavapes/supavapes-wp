<?php
/**
 * Single Product tabs
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/tabs/tabs.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Filter tabs and allow third parties to add their own.
 *
 * Each tab is an array containing title, callback and priority.
 *
 * @see woocommerce_default_product_tabs()
 */
$product_tabs = apply_filters( 'woocommerce_product_tabs', array() );

if ( ! empty( $product_tabs ) ) : 
 global $product;

 if ($product && $product->is_type('variable')) {
    $product_id = $product->get_id();
    $attributes = $product->get_attributes();
    // debug($attributes);
    ?>
    <div class="variable-table-wrap" id="variable-table-wrap">
        <div class="filter-wrap">
            <h3 class="available-options-heading"><?php echo esc_html__('Available Options', 'hello-elementor-child'); ?></h3>
            <div class="search-variation-input-wrap">
                <input type="text" name="search-variation" id="search_variation" placeholder="Enter 3 or more charcters" data-product_id="<?php echo $product_id; ?>" fdprocessedid="7jpdgp">
                <a href="javascript:void(0);" class="search-variation-enter"><svg width="22" height="20" viewBox="0 0 22 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M20 0.576172C19.6022 0.576172 19.2207 0.734207 18.9393 1.01551C18.658 1.29681 18.5 1.67834 18.5 2.07617V8.92379C18.5 9.45423 18.2893 9.96293 17.9142 10.338C17.5392 10.7131 17.0304 10.9238 16.5 10.9238H5.62135L8.56072 7.98448C8.84203 7.70317 9.00007 7.32163 9.00007 6.9238C9.00007 6.52597 8.84203 6.14443 8.56072 5.86311C8.27941 5.5818 7.89787 5.42376 7.50004 5.42376C7.1022 5.42376 6.72066 5.5818 6.43935 5.86311L0.939366 11.3631C0.80007 11.5024 0.689575 11.6677 0.614188 11.8497C0.538801 12.0317 0.5 12.2268 0.5 12.4238C0.5 12.6208 0.538801 12.8158 0.614188 12.9978C0.689575 13.1798 0.80007 13.3452 0.939366 13.4845L6.43935 18.9845C6.72066 19.2658 7.1022 19.4238 7.50004 19.4238C7.89787 19.4238 8.27941 19.2658 8.56072 18.9845C8.84203 18.7031 9.00007 18.3216 9.00007 17.9238C9.00007 17.5259 8.84203 17.1444 8.56072 16.8631L5.62135 13.9238H16.5C17.1566 13.9238 17.8068 13.7945 18.4134 13.5432C19.0201 13.2919 19.5712 12.9236 20.0355 12.4593C20.4998 11.995 20.8681 11.4438 21.1194 10.8372C21.3707 10.2306 21.5 9.5804 21.5 8.92379V2.07617C21.5 1.67834 21.342 1.29681 21.0607 1.01551C20.7794 0.734207 20.3978 0.576172 20 0.576172Z" fill="#EC4E34"></path>
                  </svg>
                </a>
            </div>
            <!-- <input type="text" name="search-variation" id="search_variation" placeholder="Please enter 3 or more charcters" data-product_id="<?php //echo $product_id; ?>"> -->
            <div class="select-filter-wrap">
                <?php if (!empty($attributes)) : ?>
                    <?php foreach ($attributes as $attribute_name => $attribute) : ?>
                        <?php if ($attribute->get_variation()) : // Only include attributes that are used for variations 
                                $attribute_name = wc_attribute_label($attribute->get_name());
                                $formatted_attribute_name = strtolower(str_replace(' ', '', $attribute_name));
                            ?>
                            <div class="filter">
                                <label><?php echo wc_attribute_label($attribute->get_name()); ?></label>
                                <select class="form-control" name="<?php echo wc_attribute_label($attribute->get_name()); ?>" id="<?php echo $formatted_attribute_name;  ?>">
                                    <option value=""><?php echo esc_html__('Any', 'hello-elementor-child'); ?></option>
                                    <?php
                                    // Check if it's a taxonomy-based attribute
                                    if ($attribute->is_taxonomy()) {
                                        $terms = wc_get_product_terms($product_id, $attribute->get_name(), array('fields' => 'all'));
                                        foreach ($terms as $term) {
                                            echo '<option value="' . esc_attr($term->slug) . '">' . esc_html($term->name) . '</option>';
                                        }
                                    } else {
                                        // For custom product attributes (non-taxonomy)
                                        $options = $attribute->get_options();
                                        foreach ($options as $option) {
                                            echo '<option value="' . esc_attr($option) . '">' . esc_html($option) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="variable-table">
            <?php echo do_shortcode('[vpe-woo-variable-product id="' . $product_id . '"]'); ?>
        </div>
    </div>
    <?php
}

?>
	<div class="woocommerce-tabs wc-tabs-wrapper">
		<ul class="tabs wc-tabs" role="tablist">
			<?php foreach ( $product_tabs as $key => $product_tab ) : ?>
				<li class="<?php echo esc_attr( $key ); ?>_tab" id="tab-title-<?php echo esc_attr( $key ); ?>" role="tab" aria-controls="tab-<?php echo esc_attr( $key ); ?>">
					<a href="#tab-<?php echo esc_attr( $key ); ?>">
						<?php echo wp_kses_post( apply_filters( 'woocommerce_product_' . $key . '_tab_title', $product_tab['title'], $key ) ); ?>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php foreach ( $product_tabs as $key => $product_tab ) : ?>
			<div class="woocommerce-Tabs-panel woocommerce-Tabs-panel--<?php echo esc_attr( $key ); ?> panel entry-content wc-tab" id="tab-<?php echo esc_attr( $key ); ?>" role="tabpanel" aria-labelledby="tab-title-<?php echo esc_attr( $key ); ?>">
				<?php
				if ( isset( $product_tab['callback'] ) ) {
					call_user_func( $product_tab['callback'], $key, $product_tab );
				}
				?>
			</div>
		<?php endforeach; ?>

		<?php do_action( 'woocommerce_product_after_tabs' ); ?>
	</div>

<?php endif; ?>
