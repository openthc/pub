<?php
/**
 *
 */

namespace OpenTHC\Pub\Controller\Lab;

class COA extends \OpenTHC\Controller\Base
{
	/**
	 *
	 */
	function __invoke($REQ, $RES, $ARG)
	{
		$want_list = explode(',', $_SERVER['HTTP_ACCEPT']);
		$want_type = strtolower($want_list[0]);
		if ( ! empty($ARG['type'])) {
			switch ($ARG['type']) {
				case 'html':
					$want_type = 'text/html';
					break;
				case 'pdf':
					$want_type = 'application/pdf';
					break;
			}
		}
		// echo "WOULD RETURN $want_type\n";

		switch ($want_type) {
			case 'application/json':
				break;
			case 'application/pdf':
				$path = sprintf('%s/var/lab/%s/*.pdf', APP_ROOT, $ARG['id']);
				$file_list = glob($path);
				if (1 == count($file_list)) {

					$file = $file_list[0];
					$name = basename($file);

					header('content-description: Data Download');
					header(sprintf('content-disposition: inline; filename="%s"', $name));
					header(sprintf('content-length: %d', filesize($file)));
					header('content-transfer-encoding: binary');
					header('content-type: application/pdf');

					readfile($file);

					exit;
				}

				break;
		}

	}

	/**
	 *
	 */
	function post($REQ, $RES, $ARG)
	{

		$sent_type = $_SERVER['CONTENT_TYPE'];
		$sent_type = strtok($sent_type, ';');

		$want_list = explode(',', $_SERVER['HTTP_ACCEPT']);
		$want_type = strtolower($want_list[0]);

		switch ($sent_type) {
			case 'application/json':
				echo "TAKE JSON UPLOAD?\n";
				break;
			case 'application/pdf':

				$data = file_get_contents('php://input');
				$name = $_GET['name'];
				$name = basename($name);
				$name = preg_replace('/\.pdf$/i', '', $name);
				$file = sprintf('%s/var/lab/%s/%s.pdf', APP_ROOT, $ARG['id'], rawurlencode($name));
				$path = dirname($file);
				if ( ! is_dir($path) ) {
					mkdir($path, 0755, true);
				}
				$ret = file_put_contents($file, $data);

				// @todo Set Expiration Time?

				return $RES->withJSON([
					'data' => $ret,
					'meta' => [
						'path' => $path,
					]
				]);

				break;

			default:
				// ERROR
				break;
		}
	}

}
