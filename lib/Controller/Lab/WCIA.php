<?php
/**
 *
 */

namespace OpenTHC\Pub\Controller\Lab;

class WCIA extends \OpenTHC\Controller\Base
{
	/**
	 *
	 */
	function __invoke($REQ, $RES, $ARG)
	{
		echo "SEND JSON\n";
	}

	/**
	 *
	 */
	function post($REQ, $RES, $ARG)
	{
		echo "TAKE JSON\n";
	}

}
