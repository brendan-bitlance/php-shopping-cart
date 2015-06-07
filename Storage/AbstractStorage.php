<?php

namespace CartUp\Storage;

use CartUp\Cart;

abstract class AbstractStorage
{
	const KEY = 'CartUp';

	/**
	 * @return Cart
	 */
	abstract public function load($id);

	/**
	 * @param Cart $cart
	 */
	abstract public function save(Cart $cart);
}
