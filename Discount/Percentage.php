<?php

namespace CartUp\Discount;

use CartUp\Calculatable;

class Percentage extends AbstractDiscount
{
	/**
	 * @var double
	 */
	private $value;

	public function __construct($code, $value)
	{
		if ($value <= 0.0 || $value > 100.0) {
			throw new \InvalidArgumentException('Value must be greater than 0 and less than or equal to 100');
		}
		parent::__construct($code);
		$this->value = (double) $value;
	}

	public function calculate($total, $decimal_places = Calculatable::DECIMAL_PLACES, $rounding = Calculatable::ROUNDING)
	{
		return round($total * $this->value / 100, $decimal_places, $rounding);
	}
}
