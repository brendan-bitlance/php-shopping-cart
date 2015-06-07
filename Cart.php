<?php

namespace CartUp;

use CartUp\Storage\AbstractStorage;
use CartUp\Storage\Session;

class Cart implements Hashable
{
	const TOTAL_UNIT = 'unit';
	const TOTAL_DISCOUNT = 'discount';
	const TOTAL_TAX = 'tax';
	const TOTAL_LINE = 'line';

	/**
	 * @var mixed
	 */
	private $id;

	/**
	 * @var AbstractStorage 
	 */
	private $storage;

	/**
	 * @var array
	 */
	private $item_lines = array();

	/**
	 * @var array
	 */
	private $totals = array();

	/**
	 * @var bool
	 */
	private $calculated = true;

	public function __construct($id, AbstractStorage $storage)
	{
		$this->id = $id;
		$this->storage = $storage;
		$this->totals = array_fill_keys(self::get_total_types(), 0.0);
	}

	public function __destruct()
	{
		$this->storage->save($this);
	}

	/**
	 * @return mixed
	 */
	public function get_id()
	{
		return $this->id;
	}

	/**
	 * @param Item $i
	 * @param int $quantity
	 */
	public function add_item(Item $i, $quantity = 1, $group = ItemLine::GROUP_DEFAULT)
	{
		$this->add_item_line(new ItemLine($i, $quantity, $group));
	}

	/**
	 * @param Item $i
	 * @return bool
	 */
	public function has_item(Item $i)
	{
		return isset($this->item_lines[$i->get_hash()]);
	}

	/**
	 * @param string $code
	 * @return bool
	 */
	public function has_item_code($code)
	{
		foreach ($this->item_lines as $il) {
			if ($il->item->code == $code) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Total quantity of items matching the provided code
	 *
	 * @param string $code
	 * @return int
	 */
	public function count_item_code($code)
	{
		$count = 0;
		foreach ($this->item_lines as $il) {
			if ($il->item->code == $code) {
				$count += $il->quantity;
			}
		}
		return $count;
	}

	/**
	 * Total number of unique item codes
	 *
	 * @return int
	 */
	public function count_items()
	{
		$count = 0;
		$item_codes = array();
		foreach ($this->item_lines as $il) {
			if (!isset($item_codes[$il->item->code])) {
				++$count;
				$item_codes[$il->item->code] = true;
			}
		}
		return $count;
	}

	/**
	 * Return ordered list of items
	 *
	 * @return array
	 */
	public function get_item_lines()
	{
		$grouped_items = array();
		foreach ($this->item_lines as $il) {
			$grouped_items[$il->group][] = $il;
		}
		return call_user_func_array('array_merge', array_map(function ($group) use (&$grouped_items) {
			if (array_key_exists($group, $grouped_items)) {
				return $grouped_items[$group];
			} else {
				return array();
			}
		}, ItemLine::get_groups()));
	}

	/**
	 * @param Item $i
	 * @param int|null $quantity
	 * @param int|null $group
	 */
	public function update_item(Item $i, $quantity = null, $group = null)
	{
		$this->assert_item($i);
		$hash = $i->get_hash();
		if (!is_null($quantity)) {
			$this->item_lines[$hash]->set_quantity($quantity);
		}
		if (!is_null($group)) {
			$this->item_lines[$hash]->set_group($group);
		}
		$this->calculated = false;
	}

	/**
	 * @param Item $i
	 */
	public function remove_item(Item $i)
	{
		$this->assert_item($i);
		unset($this->item_lines[$i->get_hash()]);
		$this->calculated = false;
	}

	/**
	 * Remove all items matching the code provided
	 *
	 * @param string $code
	 */
	public function remove_item_code($code)
	{
		foreach ($this->item_lines as $key => $il) {
			if ($il->item->code == $code) {
				$this->remove_item($il->item);
			}
		}
	}

	/**
	 * @return void
	 */
	public function calculate_totals()
	{
		// Check if anything has changed since the last call
		if ($this->calculated) {
			return;
		}

		$total_types = self::get_total_types();
		$totals = array_fill_keys($total_types, 0.0);
		foreach ($this->item_lines as $il) {
			foreach ($total_types as $type) {
				$totals[$type] += $il->{"{$type}_total"};
			}
		}
		foreach ($totals as $property => $value) {
			$this->totals[$property] = $value;
		}

		$this->calculated = true;
	}

	/**
	 * @param int $type
	 * @return double
	 * @throws \DomainException
	 */
	public function get_total($type)
	{
		if (!in_array($type, self::get_total_types())) {
			throw new \DomainException('Unknown type');
		}
		$this->calculate_totals();
		return $this->totals[$type];
	}

	/**
	 * Associative array of all calculated totals
	 *
	 * @return array
	 */
	public function get_totals()
	{
		$this->calculate_totals();
		return $this->totals;
	}

	public function get_hash()
	{
		$hash_subjects = array();
		foreach ($this->item_lines as $il) {
			$hash_subjects[] = $il->get_hash();
		}
		return md5(implode(Hashable::GLUE, $hash_subjects));
	}

	/**
	 * @param ItemLine $il
	 */
	private function add_item_line(ItemLine $il)
	{
		$hash = $il->get_hash();
		if ($this->has_item($il->item)) {
			$existing_item_line =& $this->item_lines[$hash];
			$new_quantity = $existing_item_line->quantity + $il->quantity;
			$existing_item_line->set_quantity($new_quantity);
		} else {
			$this->item_lines[$hash] = $il;
		}
		$this->calculated = false;
	}

	/**
	 * Ensure the provided item exists
	 *
	 * @param Item $i
	 * @throws \LogicException
	 */
	private function assert_item(Item $i)
	{
		if (!$this->has_item($i)) {
			throw new \LogicException("Item not found: {$i->code}");
		}
	}

	/**
	 * Load cart instance defined by a unique id and optionally a storage driver
	 *
	 * @param mixed $id
	 * @param AbstractStorage $storage
	 * @return Cart
	 */
	public static function fetch($id, AbstractStorage $storage = null)
	{
		if (is_null($storage)) {
			$storage = new Session();
		}
		return $storage->load($id);
	}

	/**
	 * List of possible total types
	 *
	 * @return array
	 */
	public static function get_total_types()
	{
		return array(
			self::TOTAL_UNIT,
			self::TOTAL_DISCOUNT,
			self::TOTAL_TAX,
			self::TOTAL_LINE
		);
	}
}
