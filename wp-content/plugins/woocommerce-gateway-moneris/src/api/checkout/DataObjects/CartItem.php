<?php

namespace SkyVerge\WooCommerce\Moneris\API\Checkout\DataObjects;

/**
 * DTO to represent a cart item in a preload request.
 * @link https://developer.moneris.com/livedemo/checkout/preload_req/guide/php
 */
class CartItem
{
	public string $description;
	public string $product_code;
	public float $unit_cost;
	public int $quantity;

	public function __construct(
		string $description,
		string $product_code,
		float $unit_cost,
		int $quantity
	) {
		$this->description = $description;
		$this->product_code = $product_code;
		$this->unit_cost = $unit_cost;
		$this->quantity = $quantity;
	}

	public function toArray(): array
	{
		return get_object_vars($this);
	}
}
