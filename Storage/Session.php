<?php

namespace CartUp\Storage;

use CartUp\Cart;

class Session extends AbstractStorage
{
	public function load($id)
	{
		if (!session_id()) {
			if (!session_start()) {
				throw new \RuntimeException('Unable to load session');
			}
		}
		if (array_key_exists(parent::KEY, $_SESSION) && array_key_exists($id, $_SESSION[parent::KEY])) {
			$cart = unserialize($_SESSION[parent::KEY][$id]);
			if (!$cart instanceof Cart) {
				throw new \RuntimeException('Bad cart data');
			}
		} else {
			$cart = new Cart($id, $this);
		}
		return $cart;
	}

	public function save(Cart $cart)
	{
		$_SESSION[parent::KEY][$cart->get_id()] = serialize($cart);
	}
}
