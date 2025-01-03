<?php

namespace SkyVerge\WooCommerce\Moneris\API\Checkout\Adapters;

use SkyVerge\WooCommerce\Moneris\API\Checkout\DataObjects\Cart;
use SkyVerge\WooCommerce\Moneris\API\Checkout\DataObjects\CartItem;
use WC_Order;
use WC_Order_Item_Product;

/**
 * Creates a {@see Cart} DTO from a WooCommerce order object.
 */
class CartBuilder
{
	public function buildFromWooOrder(WC_Order $order): Cart
	{
		return new Cart($this->buildCartItemsFromWooOrder($order), $order->get_subtotal());
	}

	/**
	 * @return CartItem[]
	 */
	protected function buildCartItemsFromWooOrder(WC_Order $order): array
	{
		$items = [];
		foreach($order->get_items() as $lineItem) {
			if (! $lineItem instanceof WC_Order_Item_Product) {
				continue;
			}

			$product = $lineItem->get_product();

			$items[] = new CartItem(
				$lineItem->get_name(),
				$product->get_sku(),
				((float) $lineItem->get_subtotal()) / $lineItem->get_quantity(),
				$lineItem->get_quantity()
			);
		}

		return $items;
	}
}
