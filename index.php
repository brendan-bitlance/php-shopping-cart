<?php

namespace CartUp;

define('CART_ID', 1);
define('CURRENCY_CODE', 'AUD');
define('CURRENCY_SYMBOL', '$');

spl_autoload_register(function($fully_qualified_name) {
	$name = str_replace('\\', DIRECTORY_SEPARATOR, $fully_qualified_name);
	$path = dirname(__DIR__) . "/{$name}.php";
	if (is_file($path)) {
		include $path;
	}
});

function money($number)
{
	echo ($number < 0 ? '-' : '') . CURRENCY_SYMBOL . number_format(abs($number), 2) . ' <span class="code">' . CURRENCY_CODE . '</span>';
}

Tax::register_taxes(array(
	'GST' => 10.0,
	'VAT' => 12.5
));

$mars_bar = new Item('MARSBAR', 1.00, 'These are pretty yummy', Tax::fetch_tax('GST'));
$free_mars_bar = new Item('FREEMARSBAR', 1.00, 'These are pretty yummy', Tax::fetch_tax('GST'), new Discount\Percentage('FREEMARSBAR', 90.00));
$random_bar = new Item('HEALTHBAR', 1.95, 'These are pretty average', Tax::fetch_tax('VAT'));
$sweet_bar = new Item('SWEETBAR', 1.25, 'These are tax free');
$delivery = new Item('DELIVERY', 2.50, 'Delivered to your door', Tax::fetch_tax('VAT'));

$cart = Cart::fetch(CART_ID);

if (array_key_exists('add', $_GET)) {

	// Add items for the first time
	$cart->add_item($mars_bar, 3);
	$cart->add_item($free_mars_bar, 1);
	$cart->add_item($mars_bar, 1); // One more for the road :)
	$cart->add_item($random_bar);
	$cart->add_item($sweet_bar);
	if (!$cart->has_item_code('DELIVERY')) {
		$cart->add_item($delivery, 1);
	}

	/*// Add global discount
	$totals = $cart->get_totals();
	if ($cart->has_item_code('MYDISCOUNT')) {
		$cart->remove_item_code('MYDISCOUNT');
	}
	$cart->add_item(new Item('MYDISCOUNT', -1 * $totals[Cart::TOTAL_LINE] * 10 / 100, '10% Off'), 1, ItemLine::GROUP_BOTTOM);*/
}

?>
<!DOCTYPE html>
<html>
	<head>
		<title><?php echo __FILE__ ?></title>
		<style>html{font:1em sans-serif}.window{width:960px;padding:1em;margin:0 auto}table{width:100%}.left{text-align:left}.right{text-align:right}.amount{width:100px}.quantity{width:40px}.code{color:#777;font-size:.6em}td,th{padding:.5em}.list th{border-bottom:2px solid #ccc}.list tr:nth-child(2n)>td{background:#f0f0f0}.list td{transition:.1s background}.list tr:hover>td{background:#eef2ff}.totals{margin-top:2em}.totals td{width:100px}</style>
	</head>
	<body>
		<div class="window">
			<h1>My Cart</h1>
			<table class="items list">
				<thead>
					<tr>
						<th class="left">Item</th>
						<th class="right">Unit Price</th>
						<th class="right quantity">Qty</th>
						<th class="right">Amount</th>
						<th class="right">Discount</th>
						<th class="right">Tax</th>
						<th class="right">Total</th>
					</tr>
				</thead>
				<tbody>
<?php
foreach ($cart->get_item_lines() as $item_line) {
?>
					<tr>
						<td class="left"><?php echo htmlspecialchars("{$item_line->item->code} - {$item_line->item->description}") ?></td>
						<td class="right amount"><?php money($item_line->unit_price) ?></td>
						<td class="right quantity"><?php echo $item_line->quantity ?></td>
						<td class="right amount"><?php money($item_line->unit_total) ?></td>
						<td class="right amount"><?php money($item_line->discount_total) ?></td>
						<td class="right amount"><?php money($item_line->tax_total) ?></td>
						<td class="right amount"><?php money($item_line->line_total) ?></td>
					</tr>
<?php
}
?>
				</tbody>
			</table>
			<table class="totals">
				<tr>
					<th class="right">Subtotal</th>
					<td class="right"><?php money($cart->get_total(Cart::TOTAL_UNIT)) ?></td>
				</tr>
				<tr>
					<th class="right">Discounts</th>
					<td class="right"><?php money($cart->get_total(Cart::TOTAL_DISCOUNT)) ?></td>
				</tr>
				<tr>
					<th class="right">Tax</th>
					<td class="right"><?php money($cart->get_total(Cart::TOTAL_TAX)) ?></td>
				</tr>
				<tr>
					<th class="right">Grand Total</th>
					<td class="right"><?php money($cart->get_total(Cart::TOTAL_LINE)) ?></td>
				</tr>
			</table>
			<p>
				<a href="<?php echo $_SERVER['PHP_SELF'] ?>?add=1">Add stuff to cart</a>
			</p>
		</div>
	</body>
</html>
