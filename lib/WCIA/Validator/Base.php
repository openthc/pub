<?php
/**
 * WCIA Validator Base
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Pub\WCIA\Validator;

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
		echo '<table class="table table-sm">';
		foreach ($report as $idx => $r) {
			echo '<tr>';
			printf('<td>%s</td>', $r[0]);
			printf('<td>%s</td>', $r[1]);
			echo '</tr>';
		}
		echo '</table>';
	}

	function _report_fail($c, $m)
	{
		return [ $c, sprintf('<div class="text-danger">%s</div>', $m) ];
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
