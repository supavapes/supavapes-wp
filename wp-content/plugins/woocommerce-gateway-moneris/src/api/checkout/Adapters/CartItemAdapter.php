<?php

namespace SkyVerge\WooCommerce\Moneris\API\Checkout\Adapters;

use SkyVerge\WooCommerce\Moneris\API\Checkout\DataObjects\CartItem;
use WC_Product;

/**
 * Creates an array of {@see CartItem} DTOs from {@see \WC_Cart} line items.
 */
class CartItemAdapter
{
	/**
	 * @param array $cartItems WooCommerce cart items from {@see \WC_Cart}
	 * @return CartItem[]
	 */
	public function convertFromSource(array $cartItems): array
	{
		$items = [];
		foreach($cartItems as $item) {
			if (! isset($item['data']) || ! $item['data'] instanceof WC_Product) {
				continue;
			}

			$items[] = new CartItem(
				$item['data']->get_title(),
				$item['data']->get_sku(),
				(float) ($item['line_subtotal'] ?? 0),
				(int) ($item['quantity'] ?? 1)
			);
		}

		return $items;
	}
}
