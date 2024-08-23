<?php
global $product;
if ($product !== null) {
    $review_count = $product->get_review_count();
}
?>
<ul class="sv-stars">
    <?php $rating_num = intval($rating); ?>
    <li>
        <?php if ($rating_num === 1) { ?>
            <img
            src="<?php echo esc_url(get_site_url()); ?>/wp-content/uploads/2024/04/1-star.png" />
        <?php } else if ($rating_num === 2) { ?>
            <img
            src="<?php echo esc_url(get_site_url()); ?>/wp-content/uploads/2024/04/2-star.png" />
        <?php } else if ($rating_num === 3) { ?>
            <img
            src="<?php echo esc_url(get_site_url()); ?>/wp-content/uploads/2024/04/3-star.png" />
        <?php } else if ($rating_num === 4) { ?>
            <img
            src="<?php echo esc_url(get_site_url()); ?>/wp-content/uploads/2024/04/4-star.png" />
        <?php } else if ($rating_num === 5) { ?>
            <img
            src="<?php echo esc_url(get_site_url()); ?>/wp-content/uploads/2024/04/5-star.png" />
        <?php } ?>
    </li>
</ul>