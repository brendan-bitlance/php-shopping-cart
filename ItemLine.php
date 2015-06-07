<?php

namespace CartUp;

class ItemLine implements Calculatable, Hashable
{
	const GROUP_DEFAULT = 1;
	const GROUP_TOP = 2;
	const GROUP_BOTTOM = 3;

	/**
	 * @var Item
	 */
	private $item;

	/**
	 * @var int
	 */
	private $quantity;

	/**
	 * @var int
	 */
	private $group;

	/**
	 * @var double
	 */
	private $unit_price = 0.0;

	/**
	 * @var double
	 */
	private $unit_total = 0.0;

	/**
	 * @var double
	 */
	private $discount_total = 0.0;

	/**
	 * @var double
	 */
	private $tax_total = 0.0;

	/**
	 * @var double
	 */
	private $line_total = 0.0;

	public function __construct(Item $i, $quantity = 1, $group = self::GROUP_DEFAULT)
	{
		$this->item = $i;
		$this->set_quantity($quantity);
		$this->set_group($group);
		$this->calculate_totals();
	}

	public function __get($name)
	{
		if (!property_exists($this, $name)) {
			throw new \BadMethodCallException();
		}
		return $this->{$name};
	}

	public function set_quantity($quantity)
	{
		$safe_quantity = (int) $quantity;
		if ($safe_quantity < 1 || $safe_quantity != $quantity) {
			throw new \InvalidArgumentException('Quantity must be numeric and greater than or equal to 1');
		}
		if ($this->quantity != $safe_quantity) {
			$this->quantity = $safe_quantity;
			$this->calculate_totals();
		}
	}

	public function set_group($group)
	{
		if (!in_array($group, self::get_groups())) {
			throw new \DomainException('Unknown group');
		}
		$this->group = $group;
	}

	public function calculate($total, $decimal_places = Calculatable::DECIMAL_PLACES, $rounding = Calculatable::ROUNDING)
	{
		return round($total, $decimal_places, $rounding);
	}

	public function calculate_totals()
	{
		// For excluding tax amounts
		if (!is_null($this->item->tax)) {
			$total_ratio = $this->item->tax->ratio;
		} else {
			$total_ratio = 1.0;
		}
		$unit_quantity_price = $this->item->price * $this->quantity;

		// Discounts
		$raw_discount_total = 0.0;
		if ($this->item->price > Calculatable::EPSILON) {
			$remaining = $this->item->price;
			foreach ($this->item->discounts as $discount) {
				$discount_amount = $discount->calculate($remaining);
				$remaining -= $discount_amount;
				$raw_discount_total += $discount_amount;
				if ($remaining < Calculatable::EPSILON) {
					$raw_discount_total = $this->item->price;
					break;
				}
			}
		}

		// Totals
		$this->unit_price = $this->item->price / $total_ratio;
		$this->unit_total = $this->calculate($unit_quantity_price / $total_ratio);
		$this->discount_total = $this->calculate($raw_discount_total / $total_ratio);
		if (!is_null($this->item->tax)) {
			$this->tax_total = $this->item->tax->calculate($unit_quantity_price - $raw_discount_total);
		}
		$this->line_total = $this->calculate($this->unit_total - $this->discount_total + $this->tax_total);
	}

	public function get_hash()
	{
		return $this->item->get_hash();
	}

	/**
	 * List of possible groups
	 *
	 * @return array
	 */
	public static function get_groups()
	{
		return array(
			self::GROUP_TOP,
			self::GROUP_DEFAULT,
			self::GROUP_BOTTOM
		);
	}
}
