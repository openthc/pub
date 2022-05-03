<?php
/**
 * Base Test Case
 *
 * SPDX-License-Identifier: MIT
 */

namespace Test;

class Base extends \PHPUnit\Framework\TestCase
{
	protected $_pid = null;
	protected $_api_base = '';

	function __construct($name = null, array $data = [], $dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->_pid = getmypid();
		$this->_api_base = rtrim(\OpenTHC\Config::get('app/base'), '/');
	}

}
