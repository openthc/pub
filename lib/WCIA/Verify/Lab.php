<?php
/**
 * WCIA Verify Lab
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Pub\WCIA\Verify;

class Lab extends Base
{
	function verify()
	{
		$doc = $this->doc;

		$report = [];
		$x = $doc['document_schema_version'];
		switch ($x) {
		case '1.0.0.0':
		case '1.0.0':
		case '1.1.0':
		case '1.2.0':
		case '1.3.0':
		case '2.0.0':
		case '2.1.0':
			$report[] = $this->_report_fail('Document Version', sprintf('Old Version %s', __h($x)));
			break;
		case '2.2.0':
			$report[] = $this->_report_good('Document Version', '2.2.0');
			break;
		default:
			$report[] = $this->_report_warn('Document Version', sprintf('Unknown Document Version <code>%s</code>', __h($x)));
			break;
		}
		unset($doc['document_schema_version']);

		$x = $doc['document_origin'];
		// Match Original URL?
		if (empty($x)) {
			$report[] = $this->_report_warn('Document Origin', 'Missing Origin');
		}
		unset($doc['document_origin']);

		// Lab Result
		$x = $doc['labresult_id'];
		$report[] = $this->_report_info('Lab Result', __h($x));
		unset($doc['labresult_id']);

		// Lab Sample
		$x = $doc['sample'];
		if ( ! empty($x)) {
			// Stuff
			if ( ! empty($x['id'])) {
				$report[] = $this->_report_info('Lab Sample', $x['id']);
			}
			// sample_source_id
		} else {
			$report[] = $this->_report_warn('Lab Sample', '-missing-');
		}
		unset($doc['sample']);

		$x = $doc['status'];
		switch ($doc['status']) {
		case 'fail':
			$report[] = $this->_report_warn('Status', 'Failed');
			break;
		case 'pass':
			$report[] = $this->_report_good('Status', 'Passed');
			break;
		default:
			$report[] = $this->_report_good('Status', sprintf('Unknown <strong>%s</strong>', __h($x)));
			break;
		}
		unset($doc['status']);

		$x = $doc['coa'];
		if ( ! empty($x)) {
			$report[] = $this->_report_info('COA Link', sprintf('<a href="%s" target="_blank">%s</a>', __h($x), __h($x)));;
		}
		unset($doc['coa']);

		foreach ($doc['metric_list'] as $i_lmg => $lmg) {
			// test_id
			// test_type == Group Name
			$report[] = $this->_report_info('Lab Metric Group', sprintf('%s [%s]', __h($lmg['test_type']), __h($lmg['test_id'])) );
			foreach ($lmg['metrics'] as $i_lrm => $lrm) {

				$k = sprintf('%s / %s', __h($lrm['analyte_type']), __h($lrm['name']));

				$v = sprintf('%s %s', __h($lrm['qom']), __h($lrm['uom']));
				switch ($lrm['status']) {
				case 'fail':
					$report[] = $this->_report_fail($k, $v);
					break;
				case 'pass':
					$report[] = $this->_report_info($k, $v);
					break;
				}

				// $x = $lrm['id'];
				// if (preg_match('/01\w{24}$/', $x)) {
				// 	// ULID Checkier?
				// }
			}
		}


		// Draw Report
		$this->_echo_report($report);

		// echo '<pre>' . __h(json_encode($doc, JSON_PRETTY_PRINT)) . '</pre>';

	}
}
