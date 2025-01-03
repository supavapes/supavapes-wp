<?php

namespace SkyVerge\WooCommerce\Moneris\API\Checkout\DataObjects;

/**
 * DTO to represent the required data and structure of the cart data in preload requests.
 * @link https://developer.moneris.com/livedemo/checkout/preload_req/guide/php
 */
class Cart
{
	/** @var CartItem[] */
	public array $items;
	public float $subtotal;

	public function __construct(array $items, float $subtotal)
	{
		$this->items = $items;
		$this->subtotal = $subtotal;
	}

	public function toArray(): array
	{
		return [
			'items' => array_map(fn(CartItem $item) => $item->toArray(), $this->items),
			'subtotal' => $this->subtotal,
		];
	}
}
