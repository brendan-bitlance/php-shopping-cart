<?php

namespace CartUp;

use CartUp\Discount\AbstractDiscount;

class Item implements Hashable
{
	/**
	 * @var string
	 */
	private $code;

	/**
	 * @var string
	 */
	private $description;

	/**
	 * @var double
	 */
	private $price;

	/**
	 * @var Tax
	 */
	private $tax;

	/**
	 * @var AbstractDiscount[]
	 */
	private $discounts = array();

	public function __construct($code, $price, $description = null, Tax $tax = null, $discounts = null)
	{
		$this->code = (string) $code;
		$this->description = (string) $description;
		$this->price = (double) $price;
		$this->tax = $tax;
		if (!is_null($discounts)) {
			if (!is_array($discounts)) {
				$discounts = array($discounts);
			}
			foreach ($discounts as $d) {
				$this->add_discount($d);
			}
		}
	}

	public function __get($name)
	{
		if (!property_exists($this, $name)) {
			throw new \BadMethodCallException();
		}
		return $this->{$name};
	}

	public function add_discount(AbstractDiscount $d)
	{
		$this->discounts[] = $d;
	}

	public function get_hash()
	{
		$hash_parts = array(
			$this->code,
			$this->price,
			md5($this->description)
		);
		if (!is_null($this->tax)) {
			$hash_parts[] = $this->tax->code;
		}
		foreach ($this->discounts as $d) {
			$hash_parts[] = $d->code;
		}
		return implode(Hashable::GLUE, $hash_parts);
	}
}
