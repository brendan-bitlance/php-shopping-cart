<?php

namespace CartUp\Storage;

use CartUp\Cart;

class Temporary extends AbstractStorage
{
	public function load($id)
	{
		return new Cart($id, $this);
	}

	public function save(Cart $cart)
	{ }
}
