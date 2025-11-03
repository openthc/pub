<?php
/**
 * Test System
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Pub\Test;

class Config_Test extends Base
{
	/**
	 * @test
	 */
	function config()
	{
		$key_list = [];
		$key_list[] = 'database/pub';
		$key_list[] = 'redis';
		$key_list[] = 'openthc/pub';

		foreach ($key_list as $key) {
			$x = \OpenTHC\Config::get($key);
			$this->assertNotEmpty($x, sprintf('Missing Config: %s', $key));
		}
	}

}
