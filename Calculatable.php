<?php

namespace CartUp;

interface Calculatable
{
	const EPSILON = 0.00001;
	const DECIMAL_PLACES = 2;
	const ROUNDING = PHP_ROUND_HALF_UP;

	/**
	 * @return double
	 */
	public function calculate($total, $decimal_places = self::DECIMAL_PLACES, $rounding = self::ROUNDING);
}
