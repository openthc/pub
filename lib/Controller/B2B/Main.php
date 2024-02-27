<?php
/**
 *
 */

namespace OpenTHC\Pub\Controller\B2B;

class Main extends \OpenTHC\Controller\Base
{
	/**
	 *
	 */
	function __invoke($REQ, $RES, $ARG)
	{
		$want_list = explode(',', $_SERVER['HTTP_ACCEPT']);
		$want_type = $want_list[0];
		echo "WOULD RETURN $want_type\n";

	}

	/**
	 *
	 */
	function post($REQ, $RES, $ARG)
	{
		echo "TAKE JSON UPLOAD\n";
	}

}
