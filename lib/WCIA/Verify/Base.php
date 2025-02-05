<?php
/**
 * WCIA Verify Base
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Pub\WCIA\Verify;

class Base
{
	protected $doc = null;

	protected $report = [];

	function __construct($doc)
	{
		$this->doc = $doc;
	}

	function _echo_report($report)
	{
		echo '<table class="table table-sm table-borderless table-hover mb-0">';
		foreach ($report as $idx => $r) {
			echo '<tr>';
			printf('<td>%s</td>', $r[0]);
			printf('<td>%s</td>', $r[1]);
			echo '</tr>';
		}
		echo '</table>';
	}

	function _get($url)
	{
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

		return [
			'code' => $inf['http_code'],
			'data' => [
				'head' => $res_head,
				'body' => $res,
			],
			'meta' => $inf,
		];
	}

	function _report_fail($c, $m)
	{
		return [ $c, sprintf('<div class="text-danger fw-bold">%s</div>', $m) ];
	}

	function _report_good($c, $m)
	{
		return [ $c, sprintf('<div class="text-success">%s</div>', $m) ];
	}

	function _report_info($c, $m)
	{
		return [ $c, $m ];
	}

	function _report_warn($c, $m)
	{
		return [ $c, sprintf('<div class="text-warning">%s</div>', $m) ];
	}


}
