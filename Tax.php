<?php

namespace CartUp;

class Tax implements Calculatable
{
	/**
	 * @var string
	 */
	private $code;

	/**
	 * @var double
	 */
	private $rate;

	/**
	 * @var double
	 */
	private $ratio;

	/**
	 * @var array
	 */
	private static $taxes = array();

	public function __construct($code, $rate)
	{
		$this->code = (string) $code;
		if ($rate < 0 || $rate > 100) {
			throw new \InvalidArgumentException('Rate must be between 0 and 100 inclusive');
		}
		$this->rate = (double) $rate;
		$this->ratio = 1 + $this->rate / 100;
	}

	public function __get($name)
	{
		if (!property_exists($this, $name)) {
			throw new \BadMethodCallException();
		}
		return $this->{$name};
	}

	public function calculate($total, $decimal_places = Calculatable::DECIMAL_PLACES, $rounding = Calculatable::ROUNDING)
	{
		return round($total - $total / $this->ratio, $decimal_places, $rounding);
	}

	/**
	 * @param string $code
	 * @return Tax
	 * @throws \OutOfRangeException
	 */
	public static function fetch($code)
	{
		if (!isset(self::$taxes[$code])) {
			throw new \OutOfRangeException("Tax code has not been registered: {$code}");
		}
		return self::$taxes[$code];
	}

	/**
	 * @param array $taxes Associative array (code => rate)
	 * @throws \OverflowException
	 */
	public static function register(array $taxes)
	{
		foreach ($taxes as $code => $rate) {
			try {
				$tax = self::fetch($code);
				if ($rate != $tax->rate) {
					throw new \OverflowException("Tax code already registered: {$code}");
				}
			} catch (\OutOfRangeException $ex) {
				$tax = new self($code, $rate);
				self::$taxes[$tax->code] = $tax;
			}
		}
	}
}
