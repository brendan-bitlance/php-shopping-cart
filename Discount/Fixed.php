<?php

namespace CartUp\Discount;

use CartUp\Calculatable;

class Fixed extends AbstractDiscount
{
	/**
	 * @var double
	 */
	private $value;

	public function __construct($code, $value)
	{
		if ($value <= 0.0) {
			throw new \InvalidArgumentException('Value must be greater than 0');
		}
		parent::__construct($code);
		$this->value = (double) $value;
	}

	public function calculate($total, $decimal_places = Calculatable::DECIMAL_PLACES, $rounding = Calculatable::ROUNDING)
	{
		return round(min($total, $this->value), $decimal_places, $rounding);
	}
}
