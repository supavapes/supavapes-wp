<?php

namespace SkyVerge\WooCommerce\Moneris\API\Checkout\Adapters;

use SkyVerge\WooCommerce\Moneris\API\Checkout\DataObjects\Cart;
use SkyVerge\WooCommerce\Moneris\API\Checkout\DataObjects\CartItem;
use WC_Cart;
use WC_Product;

/**
 * Creates a {@see Cart} DTO from a WooCommerce {@see WC_Cart} object.
 */
class CartAdapter
{
	protected CartItemAdapter $cartItemAdapter;

	public function __construct()
	{
		$this->cartItemAdapter = new CartItemAdapter();
	}

	public function convertFromSource(WC_Cart $cart) : Cart
	{
		return new Cart($this->convertItems($cart), $cart->get_subtotal());
	}

	protected function convertItems(WC_Cart $cart) : array
	{
		return $this->cartItemAdapter->convertFromSource($cart->get_cart());
	}
}
