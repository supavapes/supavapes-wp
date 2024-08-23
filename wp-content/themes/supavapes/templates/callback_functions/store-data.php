<?php
// Ensure the required variables are set
if (!isset($product_id) || !isset($terms) || !isset($distance_km)) {
    return; // Exit if required variables are not set
}

// Prepare the term data
$term_data = array();
foreach ($terms as $term_val) {
    $term_data[] = array(
        'name' => $term_val->name,
        'address_line_1' => get_term_meta($term_val->term_id, 'address_line_1', true),
        'address_line_2' => get_term_meta($term_val->term_id, 'address_line_2', true),
        'contact_number' => get_term_meta($term_val->term_id, 'contact_number', true),
        'pickup_avialablility' => get_term_meta($term_val->term_id, 'pickup_avialablility', true)
    );
}
?>

<div class="surface-pick-up-modal__header">
    <h2 class="surface-pick-up-modal__title"><?php echo esc_html(get_the_title($product_id)); ?></h2>
    <!-- <div class="surface-pick-up-modal__variant">Blue Razz</div> -->
</div>
<ul class="surface-pick-up-items" role="list">
    <?php foreach ($term_data as $store): ?>
        <li class="surface-pick-up-item surface-pick-up-item--available" data-surface-pick-up-item="">
            <div class="surface-pick-up-item__header">
                <h3 class="surface-pick-up-item__pick-up-location"><?php echo esc_html($store['name']); ?></h3>
                <p class="surface-pick-up-item__pick-up-distance">
                    <span data-distance="" data-latitude="45.607124" data-longitude="-74.584797"><?php echo esc_html($distance_km); ?></span>
                    <span data-distance-unit="metric"><?php esc_html_e('km','hello-elementor-child'); ?></span>
                </p>
            </div>
            <?php if($store['pickup_avialablility'] === 1){?>
              <div class="surface-pick-up-item__availability"> 
                <svg width="14" height="15" class="surface-pick-up-icon" viewBox="0 0 14 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M13.6747 2.88135C13.2415 2.44761 12.5381 2.44789 12.1044 2.88135L5.03702 9.94902L1.89587 6.8079C1.46213 6.37416 0.759044 6.37416 0.325304 6.8079C-0.108435 7.24163 -0.108435 7.94472 0.325304 8.37846L4.25157 12.3047C4.4683 12.5215 4.7525 12.6301 5.03672 12.6301C5.32094 12.6301 5.6054 12.5217 5.82213 12.3047L13.6747 4.45189C14.1084 4.01845 14.1084 3.31507 13.6747 2.88135Z" fill="#51A551"></path>
                </svg>            
                <?php esc_html_e('Pickup available, usually ready in 24 hours','hello-elementor-child'); ?>            
              </div>
              <?php }else{ ?>
              <div class="surface-pick-up-item__availability unavailable"> 
                <svg width="15" height="15" class="surface-pick-up-icon" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M13.7331 11.7904L2.76068 0.817889C2.28214 0.339349 1.50629 0.339349 1.02848 0.817889L0.450213 1.39528C-0.0283263 1.87397 -0.0283263 2.64981 0.450213 3.12759L11.4227 14.1001C11.9014 14.5786 12.6772 14.5786 13.155 14.1001L13.7324 13.5227C14.2118 13.0449 14.2118 12.2689 13.7331 11.7904Z" fill="#EC4E34"/>
                  <path d="M11.4227 0.818263L0.450213 11.7908C-0.0283263 12.2693 -0.0283263 13.0453 0.450213 13.5231L1.0276 14.1005C1.50629 14.579 2.28214 14.579 2.75991 14.1005L13.7331 3.12873C14.2118 2.65019 14.2118 1.87434 13.7331 1.39657L13.1557 0.819181C12.6772 0.339723 11.9014 0.339723 11.4227 0.818263Z" fill="#EC4E34"/>
                </svg>
                <?php esc_html_e('Pickup currently unavailable','hello-elementor-child'); ?>           
              </div> 
            <?php }?>
            <address class="surface-pick-up-item__address-info">
                <p>
                    <?php echo esc_html($store['address_line_1']); ?><br>
                    <?php echo esc_html($store['address_line_2']); ?><br>
                    <a href="tel:<?php echo esc_html($store['contact_number']); ?>"><?php echo esc_html($store['contact_number']); ?></a>
                </p>
            </address>
        </li>
    <?php endforeach; ?>   
</ul>