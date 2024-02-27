<?php
/**
 *
 */

namespace OpenTHC\Pub\Controller\Lab;

class Main extends \OpenTHC\Controller\Base
{
	/**
	 *
	 */
	function __invoke($REQ, $RES, $ARG)
	{
		$want_list = explode(',', $_SERVER['HTTP_ACCEPT']);
		$want_type = strtolower($want_list[0]);
		echo "WOULD RETURN $want_type\n";
		// var_dump($want_list);
	}

	/**
	 *
	 */
	function post($REQ, $RES, $ARG)
	{
		echo "TAKE JSON or PDF(==COA) UPLOAD\n";
	}

}
