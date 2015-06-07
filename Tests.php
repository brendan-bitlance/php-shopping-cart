<?php

spl_autoload_register(function($fully_qualified_name) {
	$name = str_replace('\\', DIRECTORY_SEPARATOR, $fully_qualified_name);
	$path = dirname(__DIR__) . "/{$name}.php";
	if (is_file($path)) {
		include $path;
	}
});

use CartUp\Cart as Cart;
use CartUp\Discount as Discount;
use CartUp\Item as Item;
use CartUp\Storage as Storage;
use CartUp\Tax as Tax;

class Tests extends PHPUnit_Framework_TestCase
{
	const TAX_GST = 'GST';
	const TAX_VAT = 'VAT';

	const PRODUCT_CODE_BICYCLE = 1;
	const PRODUCT_CODE_BLENDER = 2;
	const PRODUCT_CODE_YOYO = 3;

	const CART_ID = 'cart';

	/**
	 * Taxes
	 *
	 * @var array
	 */
	private $tax_lookups = array(
		self::TAX_GST => 10.0,
		self::TAX_VAT => 13.0
	);

	/**
	 * Products
	 *
	 * @var array
	 */
	private $products = array();

	public function setUp()
	{
		parent::setUp();

		Tax::register_taxes($this->tax_lookups);

		$this->products = array(
			self::PRODUCT_CODE_BICYCLE => new Item(self::PRODUCT_CODE_BICYCLE, 99.95, 'Check out my ride', Tax::fetch_tax(self::TAX_GST)),
			self::PRODUCT_CODE_BLENDER => new Item(self::PRODUCT_CODE_BLENDER, 42.33, 'Mix it up', Tax::fetch_tax(self::TAX_VAT)),
			self::PRODUCT_CODE_YOYO => new Item(self::PRODUCT_CODE_YOYO, 0.49, 'BYO string')
		);
	}

	public function testSanity()
	{
		$this->assertCount(2, $this->tax_lookups);
		$this->assertCount(3, $this->products);
	}

	public function testTax()
	{
		foreach (array('GST' => 10.0, 'VAT' => 13.0) as $code => $rate) {
			$tax = Tax::fetch_tax($code);
			$this->assertNotNull($tax);
			$this->assertEquals($code, $tax->code);
			$this->assertEquals($rate, $tax->rate);
		}

		$tax = Tax::fetch_tax('GST');
		$this->assertEquals(0.91, $tax->calculate(10));
		$this->assertEquals(0.9091, $tax->calculate(10, 4));
		$this->assertEquals(3.03, $tax->calculate(33.33));
		$this->assertEquals(3.03, $tax->calculate(33.3333));
		$this->assertEquals(3.03, $tax->calculate(33.34));

		$tax = Tax::fetch_tax('VAT');
		$this->assertEquals(1.15, $tax->calculate(10));
		$this->assertEquals(1.1504, $tax->calculate(10, 4));
		$this->assertEquals(3.83, $tax->calculate(33.33));
		$this->assertEquals(3.83, $tax->calculate(33.3333));
		$this->assertEquals(3.84, $tax->calculate(33.34));
	}

	public function testCart()
	{
		$cart = Cart::fetch(self::CART_ID, new Storage\Temporary());
		$this->assertEquals($cart->get_id(), self::CART_ID);
		$this->assertEquals(0, $cart->count_items());
		$this->assertEquals(0, $cart->count_item_code(self::PRODUCT_CODE_YOYO));

		$cart->add_item($this->products[self::PRODUCT_CODE_BICYCLE]);
		$this->assertTrue($cart->has_item($this->products[self::PRODUCT_CODE_BICYCLE]));
		$this->assertTrue($cart->has_item_code(self::PRODUCT_CODE_BICYCLE));
		$this->assertFalse($cart->has_item($this->products[self::PRODUCT_CODE_BLENDER]));
		$this->assertEquals(1, $cart->count_items());
		$this->assertEquals(1, $cart->count_item_code(self::PRODUCT_CODE_BICYCLE));
		$this->assertEquals(0, $cart->count_item_code(self::PRODUCT_CODE_YOYO));

		$cart->add_item($this->products[self::PRODUCT_CODE_BLENDER], 2);
		$this->assertTrue($cart->has_item($this->products[self::PRODUCT_CODE_BLENDER]));
		$this->assertTrue($cart->has_item_code(self::PRODUCT_CODE_BLENDER));
		$this->assertFalse($cart->has_item($this->products[self::PRODUCT_CODE_YOYO]));
		$this->assertEquals(2, $cart->count_items());
		$this->assertEquals(1, $cart->count_item_code(self::PRODUCT_CODE_BICYCLE));
		$this->assertEquals(2, $cart->count_item_code(self::PRODUCT_CODE_BLENDER));
		$this->assertEquals(0, $cart->count_item_code(self::PRODUCT_CODE_YOYO));

		$cart->update_item($this->products[self::PRODUCT_CODE_BICYCLE], 3);
		$this->assertTrue($cart->has_item($this->products[self::PRODUCT_CODE_BICYCLE]));
		$this->assertTrue($cart->has_item_code(self::PRODUCT_CODE_BICYCLE));
		$this->assertFalse($cart->has_item($this->products[self::PRODUCT_CODE_YOYO]));
		$this->assertEquals(2, $cart->count_items());
		$this->assertEquals(3, $cart->count_item_code(self::PRODUCT_CODE_BICYCLE));
		$this->assertEquals(2, $cart->count_item_code(self::PRODUCT_CODE_BLENDER));
		$this->assertEquals(0, $cart->count_item_code(self::PRODUCT_CODE_YOYO));

		$cart->add_item($this->products[self::PRODUCT_CODE_YOYO]);
		$cart->remove_item_code(self::PRODUCT_CODE_YOYO);
		$this->assertEquals(2, $cart->count_items());
		$this->assertEquals(3, $cart->count_item_code(self::PRODUCT_CODE_BICYCLE));
		$this->assertEquals(2, $cart->count_item_code(self::PRODUCT_CODE_BLENDER));
		$this->assertEquals(0, $cart->count_item_code(self::PRODUCT_CODE_YOYO));

		try {
			$cart->update_item($this->products[self::PRODUCT_CODE_YOYO], 2);
			$this->fail('Product should not exist in cart');
		} catch (LogicException $ex) { }

		$this->assertEquals(347.51, $cart->get_total(Cart::TOTAL_UNIT));
		$this->assertEquals(0.00, $cart->get_total(Cart::TOTAL_DISCOUNT));
		$this->assertEquals(37.00, $cart->get_total(Cart::TOTAL_TAX));
		$this->assertEquals(384.51, $cart->get_total(Cart::TOTAL_LINE));

		$cart->remove_item_code(self::PRODUCT_CODE_BLENDER);
		$discounted_blender = clone $this->products[self::PRODUCT_CODE_BLENDER];
		$discounted_blender->add_discount(new Discount\Fixed('F01', 5.00));
		$cart->add_item($discounted_blender, 2);

		$this->assertEquals(347.51, $cart->get_total(Cart::TOTAL_UNIT));
		$this->assertEquals(4.42, $cart->get_total(Cart::TOTAL_DISCOUNT));
		$this->assertEquals(36.42, $cart->get_total(Cart::TOTAL_TAX));
		$this->assertEquals(379.51, $cart->get_total(Cart::TOTAL_LINE));

		$items = $cart->get_item_lines();
		$this->assertCount(2, $items);
		$this->assertEquals(self::PRODUCT_CODE_BICYCLE, $items[0]->item->code);
		$this->assertEquals(self::PRODUCT_CODE_BLENDER, $items[1]->item->code);

		$cart->add_item($this->products[self::PRODUCT_CODE_BICYCLE]);
		$this->assertEquals(438.37, $cart->get_total(Cart::TOTAL_UNIT));
		$this->assertEquals(4.42, $cart->get_total(Cart::TOTAL_DISCOUNT));
		$this->assertEquals(45.51, $cart->get_total(Cart::TOTAL_TAX));
		$this->assertEquals(479.46, $cart->get_total(Cart::TOTAL_LINE));

		$cart->remove_item($this->products[self::PRODUCT_CODE_BICYCLE]);
		$this->assertEquals(74.92, $cart->get_total(Cart::TOTAL_UNIT));
		$this->assertEquals(4.42, $cart->get_total(Cart::TOTAL_DISCOUNT));
		$this->assertEquals(9.16, $cart->get_total(Cart::TOTAL_TAX));
		$this->assertEquals(79.66, $cart->get_total(Cart::TOTAL_LINE));
	}
}
