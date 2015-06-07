<?php

namespace CartUp\Discount;

use CartUp\Calculatable;
use CartUp\Hashable;

abstract class AbstractDiscount implements Calculatable, Hashable
{
	/**
	 * @var string
	 */
	private $code;

	public function __construct($code)
	{
		$this->code = (string) $code;
	}

	public function __get($name)
	{
		if (!property_exists($this, $name)) {
			throw new \BadMethodCallException();
		}
		return $this->{$name};
	}

	public function get_hash()
	{
		$hash_parts = array(
			$this->code
		);
		return implode(Hashable::GLUE, $hash_parts);
	}
}
