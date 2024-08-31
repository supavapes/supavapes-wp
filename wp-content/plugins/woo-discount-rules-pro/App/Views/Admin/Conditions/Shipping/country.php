<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
echo ($render_saved_condition == true) ? '' : '<div class="shipping_country">';
$operator = isset($options->operator) ? $options->operator : 'in_list';
$values = isset($options->value) ? $options->value : false;
?>
<div class="wdr_shipping_country_group wdr-condition-type-options">
    <div class="wdr-country-method wdr-select-filed-hight">
        <select name="conditions[<?php echo (isset($i)) ? esc_attr($i) : '{i}' ?>][options][operator]" class="awdr-left-align">
            <option value="in_list" <?php echo ($operator == "in_list") ? "selected" : ""; ?>><?php esc_html_e('In List', 'woo-discount-rules-pro'); ?></option>
            <option value="not_in_list" <?php echo ($operator == "not_in_list") ? "selected" : ""; ?>><?php esc_html_e('Not In List', 'woo-discount-rules-pro'); ?></option>
        </select>
        <span class="wdr_desc_text awdr-clear-both "><?php esc_html_e('Countries should be', 'woo-discount-rules-pro'); ?></span>
    </div>

    <div class="wdr_country_value wdr-select-filed-hight wdr-search-box">
        <select multiple
                class="get_awdr_shipping_country <?php echo ($render_saved_condition == true) ? 'edit-preloaded-values' : '' ?>"
                data-list="countries"
                data-field="preloaded"
                data-placeholder="<?php esc_attr_e('Search Country', 'woo-discount-rules-pro') ?>"
                name="conditions[<?php echo (isset($i)) ? esc_attr($i) : '{i}' ?>][options][value][]"><?php
            if ($values) {
                $countries_list = \Wdr\App\Helpers\Woocommerce::getCountriesList();
                foreach ($values as $value) {
                    if (isset($countries_list[$value])) {
                        ?>
                        <option value="<?php echo esc_attr($value); ?>"
                                selected><?php echo esc_html($countries_list[$value]); ?></option><?php
                    }
                }
            }
            ?>
        </select>
        <span class="wdr_select2_desc_text"><?php esc_html_e('Select Country', 'woo-discount-rules-pro'); ?></span>
    </div>
</div>
<?php echo ($render_saved_condition == true) ? '' : '</div>'; ?>
