<?php
/**
 * WCIA Tools
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Pub\Controller;

// use OpenTHC\Sodium;

class WCIA extends Base
{
	/**
	 *
	 */
	function __invoke($REQ, $RES, $ARG)
	{
		$data = [];
		$data['page_title'] = 'WCIA Validator';

		return $RES->write($this->render('wcia.php', $data));
	}

	/**
	 * Fetch the Document and Analyse
	 */
	function post($REQ, $RES, $ARG)
	{
		// var_dump($_POST);
		// var_dump($_FILES);

		// Prefer Link
		if ( ! empty($_POST['wcia-link'])) {

			$url = $_POST['wcia-link'];
			$url = trim($url);
			if ( ! preg_match('/^http/', $url)) {
				echo $this->_alert_fail('Invalid Link; needs to start with <strong>http</strong>');
			}

			$req = _curl_init($url);
			curl_setopt($req, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($req, CURLOPT_MAXREDIRS, 2);
			curl_setopt($req, CURLOPT_TIMEOUT, 8);
			// Add Origin Header?
			curl_setopt($req, CURLOPT_HTTPHEADER, [
				sprintf('origin: https://pub.openthc.dev')
			]);
			$res_head = [];
			curl_setopt($req, CURLOPT_HEADERFUNCTION, function($req0, $head_line) use (&$res_head) {
				$idx = strpos($head_line, ':', 1);
				if ($idx >= 1) {
					$key = trim(strtolower(substr($head_line, 0, $idx)));
					$val = trim(substr($head_line, $idx + 1));
					$res_head[$key] = $val;
				} elseif ('HTTP' == substr($head_line, 0, 4)) {
					$res_head['HTTP'] = trim($head_line);
				}
				return strlen($head_line);
			});
			$res = curl_exec($req);
			$inf = curl_getinfo($req);
			curl_close($req);

			$x = [];
			if ( ! empty($inf['redirect_count'])) {
				$x[] = sprintf('Redirected to <code>%s</code>', $inf['url']);
			}
			if ( ! empty($inf['total_time'])) {
				$x[] = sprintf('Time: %0.3fs', $inf['total_time']);
			}
			$x = implode('; ', $x);

			echo '<h2 class="text-bg-primary rounded p-1">';
			echo 'HTTP Result';
			// echo ' <button data-toggle="collapse" data-target="#http-header-wrap" id="http-header-frob" type="button">Show</button>';
			echo '</h2>';
			echo '<div class="" id="http-header-wrap">';
			echo '<table class="table table-sm">';
			printf('<tr><td>%s</td><td>%s</td></tr>', __h($res_head['HTTP']), $x);
			unset($res_head['HTTP']);

			ksort($res_head);
			foreach ($res_head as $k => $v) {
				echo '<tr>';
				printf('<td>%s</td>', __h($k));
				// ')"$k: $v\n";
				switch ($k) {
				case 'content-disposition':
				case 'content-type':
				case 'set-cookie': // Not Really Needed
				default:
					printf('<td>%s</td>', __h($v));
				}
				echo '</tr>';
			}
			echo '</table>';
			echo '</div>';
			// echo '<pre>' . __h(json_encode($inf, JSON_PRETTY_PRINT)) . '</pre>';

			switch ($res_head['content-type']) {
			case 'application/json':
				$doc = json_decode($res, true);
				if (empty($doc)) {
					// $x = json_last_error_msg();
					echo $this->_alert_fail('Invalid JSON Data');
					exit;
				}
				$doc['@origin'] = $url;
				$this->verify_wcia_data($doc);
				break;
			case 'application/pdf':
				$pdf_b64 = base64_encode($res);
				echo '<iframe';
				echo ' style="height: 50vh; width: 100%;"';
				printf(' src="data:application/pdf;base64,%s"', $pdf_b64);
				echo '</iframe>';
				exit(0);
				break;
			default:
				$x = sprintf('Invalid Content Type: <strong>%s</strong>.', __h($res_head['content-type']));
				echo $this->_alert_fail($x);
				exit;
			}

			exit;
		}

		if (0 == $_FILES['wcia-file']['error']) {

			$type = $_FILES['wcia-file']['type'];
			$data = file_get_contents($_FILES['wcia-file']['tmp_name']);
			// Do Stuff
			// var_dump($_FILES['wcia-file']);
			$data = json_decode($data, true);
			if (empty($data)) {
				echo $this->_alert_fail('Invalid Data File');
				exit;
			}
		}

		echo $this->_alert_fail('Invalid Link or File');

		exit;
	}

	function _alert_fail($h)
	{
		return sprintf('<div class="alert alert-danger">%s</div>', $h);
	}

	function verify_wcia_data($doc)
	{
		unset($doc['@contact']);

		echo '<h2 class="text-bg-success rounded p-1">WCIA Data</h2>';
		$x = strtoupper($doc['document_name']);
		unset($doc['document_name']);
		switch ($x) {
		case 'WCIA LAB RESULT SCHEMA':
			$this->verify_wcia_data_lab($doc);
			break;
		case 'WCIA TRANSFER SCHEMA':
			$this->verify_wcia_data_b2b($doc);
			break;
		}
	}

	function verify_wcia_data_b2b($doc)
	{
		$v = new \OpenTHC\Pub\WCIA\Verify\B2B($doc);
		$v->verify();
	}

	function verify_wcia_data_lab($doc)
	{
		$v = new \OpenTHC\Pub\WCIA\Verify\Lab($doc);
		$v->verify();
	}
}
