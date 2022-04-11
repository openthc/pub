<?php
/**
 * Base Test Case
 */

namespace Test;

class Base extends \PHPUnit\Framework\TestCase
{
	protected $_pid = null;
	protected $_tmp_file = '';
	protected $_api_base = '';

	function __construct($name = null, array $data = [], $dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->_pid = getmypid();
		$this->_api_base = rtrim(\OpenTHC\Config::get('app/base'), '/');
		$this->_tmp_file = '/tmp/test.tmp';
	}

	/**
	 *
	 */
	protected function _data_stash_get()
	{
		if (is_file($this->_tmp_file)) {
			$x = file_get_contents($this->_tmp_file);
			$x = json_decode($x, true);
			return $x;
		}
	}

	/**
	 *
	 */
	protected function _data_stash_put($d)
	{
		file_put_contents($this->_tmp_file, json_encode($d));
	}

}
