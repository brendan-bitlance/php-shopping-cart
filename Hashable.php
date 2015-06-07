<?php

namespace CartUp;

interface Hashable
{
	const GLUE = '_';

	/**
	 * @return string
	 */
	public function get_hash();
}
