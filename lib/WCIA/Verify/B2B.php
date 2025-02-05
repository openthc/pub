<?php
/**
 * WCIA Verify B2B
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Pub\WCIA\Verify;

class B2B extends Base
{
	function verify()
	{
		$doc = $this->doc;

		$report = [];
		switch ($doc['document_schema_version']) {
		case '1.0.0.0':
		case '1.0.0':
		case '1.1.0':
		case '1.2.0':
		case '1.3.0':
		case '2.0.0':
			$report[] = $this->_report_warn('Document Version', 'Old Version');
			break;
		case '2.1.0':
		// case '2.2.0':
			$report[] = $this->_report_good('Document Version', '2.1.0', 'Current Version');
			break;
		default:
			$report[] = $this->_report_fail('Document Version', 'Unknown Document Version');
		}
		unset($doc['document_schema_version']);

		$x = $doc['document_origin'];
		// Match Original URL?
		if (empty($x)) {
			$report[] = $this->_report_warn('Document Origin', 'Missing Origin');
		}
		unset($doc['document_origin']);

		$x = $doc['transfer_id'];
		if (empty($x)) {
			$report[] = $this->_report_fail('B2B Transfer ID', 'Missing');
		} else {
			$report[] = $this->_report_info('B2B Transfer ID', __h($x));
		}
		unset($doc['transfer_id']);

		$x = $doc['manifest_type'];
		if ( ! preg_match('/(delivery|pick\-up|transporter)/', $x)) {
			$report[] = $this->_report_fail('B2B Transfer Type', sprintf('Invalid Type: <strong>%s</strong>', __h($x)));
		}
		unset($doc['manifest_type']);


		// From License
		$x = $doc['from_license_number'];
		if (empty($x)) {
			$report[] = $this->_report_fail('Origin License', 'Missing License Number');
		}
		$x = $doc['from_license_name'];
		if (empty($x)) {
			$report[] = $this->_report_warn('Origin License', 'Missing License Name');
		}

		// To License
		$x = $doc['to_license_number'];
		if (empty($x)) {
			$report[] = $this->_report_fail('Target License', 'Missing License Number');
		}
		$x = $doc['to_license_name'];
		if (empty($x)) {
			$report[] = $this->_report_warn('Target License', 'Missing License Name');
		}
		// to_license_type

		$key_list = [];
		$key_list[] = 'created_at';
		$key_list[] = 'updated_at';
		$key_list[] = 'transferred_at';
		$key_list[] = 'est_departed_at';
		$key_list[] = 'est_arrival_at';
		foreach ($key_list as $key) {
			$x = $doc[$key];
			if ( ! preg_match('/^\d{4}\-\d{2}\-\d{2}T\d{2}:\d{2}:\d{2}/', $x)) {
				$report[] = $this->_report_warn('DateTime', sprintf('Value in <code>%s<code> is not valid ISO', $key));
			}
			unset($doc[$key]);
		}

		// integrator_data
		// route

		$this->_echo_report($report);

		foreach ($doc['inventory_transfer_items'] as $idx => $b2b_item) {

			echo '<div class="mb-2">';
			printf('<h3 class="text-bg-secondary rounded p-1">Inventory Item %d</h3>', $idx + 1);

			$report = [];

			$x = $b2b_item['inventory_id'];
			if (empty($x)) {
				$report[] = $this->_report_fail('Inventory ID', 'Missing Inventory ID');
			} else {
				$report[] = $this->_report_info('Inventory ID', __h($x));
			}
			unset($b2b_item['inventory_id']);

			$x = $b2b_item['external_id'];
			if ( ! empty($x)) {
				$report[] = $this->_report_info('External ID', 'Includes Optional <code>external_id</code>');
			}
			unset($b2b_item['external_id']);

			$x = $b2b_item['product_sku'];
			if ( ! empty($x)) {
				$report[] = $this->_report_info('Product SKU', 'Includes Optional <code>product_sku</code>');
			}
			unset($b2b_item['product_sku']);

			$x1 = $b2b_item['inventory_category'];
			$x2 = $b2b_item['inventory_type'];
			$report[] = $this->_report_info('Product Category / Type', sprintf('%s / %s', __h($x1), __h($x2)));
			unset($b2b_item['inventory_category']);
			unset($b2b_item['inventory_type']);

			$report[] = $this->_report_info('Product Name', __h($b2b_item['product_name']));
			unset($b2b_item['product_name']);
			$report[] = $this->_report_info('Variety Name', __h($b2b_item['strain_name']));
			unset($b2b_item['strain_name']);

			// $x = floatval($b2b_item['qty']);

			// line_price
			if ( ! empty($b2b_item['lab_result_data']['lab_result_id'])) {
				$report[] = $this->_report_info('Lab Result', $b2b_item['lab_result_data']['lab_result_id']);
				unset($b2b_item['lab_result_data']['lab_result_id']);
			}

			// Lab Result Status
			$x1 = $b2b_item['lab_result_passed'];
			$x2 = $b2b_item['lab_result_data']['lab_result_status'];
			if (! empty($x1) && ! empty($x2)) {
				if ($x1 != $x2) {
					$report[] = $this->_report_fail('Lab Result Sstatus', sprintf('Two Unique Values? %s and %s', __h($x1), __h($x2)));
				}
			}
			switch ($x1) {
			case 'fail':
				$report[] = $this->_report_fail('Lab Result Status', sprintf('<strong>%s</strong>', __h($x1)));
				break;
			case 'pass':
				$report[] = $this->_report_good('Lab Result Status', sprintf('<strong>%s</strong>', __h($x1)));
				break;
			default:
				$report[] = $this->_report_warn('Lab Result Status', sprintf('<strong>%s</strong>', __h($x1)));
				break;
			}
			unset($b2b_item['lab_result_passed']);
			unset($b2b_item['lab_result_data']['lab_result_status']);

			// Lab Result Link
			$x = $b2b_item['lab_result_link'];
			if ( ! empty($x)) {
				$report[] = $this->_report_info('Lab Result Data Link', sprintf('<a href="?source=%s" target="_blank">%s</a>', __h($x), __h($x)));
			}

			$url = $b2b_item['lab_result_link'];
			$res = $this->_get($url);
			$lab_data = json_decode($res['data']['body'], true);
			// echo '<pre>';
			// echo __h(json_encode($lab_data, JSON_PRETTY_PRINT));
			// echo '</pre>';
			if (empty($lab_data) || ! is_array($lab_data)) {
				$report[] = $this->_report_fail('Lab Result Data', 'Failed to Fetch Lab Link');
			} else {
				// $sub_verify = Lab($lab_data);
				// $sub_report = $sub_verify->verify();
			}
			unset($b2b_item['lab_result_link']);

			if (empty($b2b_item['lab_result_data']['coa'])) {
				$report[] = $this->_report_warn('Lab Result COA', 'Not Included');
			} else {

				$x = $b2b_item['lab_result_data']['coa'];
				$report[] = $this->_report_info('Lab Result COA', sprintf('<a href="?source=%s" target="_blank">%s</a>', __h($x), __h($x)));
				// $t0 = microtime(true);
				$res = $this->_get($x);

				// echo '<pre>';
				// echo __h(json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
				// echo '</pre>';
				// exit;

				// $t1 = microtime(true);
				switch ($res['code']) {
				case 200:
					switch ($res['meta']['content_type']) {
					case 'application/pdf':
						$buf = substr($res['data']['body'], 0, 8);
						$report[] = $this->_report_good('Lab Result COA Data', sprintf('Valid PDF Type: %s', __h($buf)));
						break;
					default:
						$report[] = $this->_report_warn('Lab Result COA Data', sprintf('Invalid HTTP Content Type %s', $res['meta']['content_type']));
					}
					// OK
					break;
				default:
					$report[] = $this->_report_warn('Lab Result COA Data', sprintf('Invalid HTTP Response Code %d', $res['code']));
					break;
				}
				// $this->_report_info('Lab Result COA',
				// unset($res['data']['body']);
			}
			unset($b2b_item['lab_result_data']['coa']);

			if (empty($b2b_item['lab_result_data']['potency'])) {
				$report[] = $this->_report_warn('Lab Result Potency Data', 'Not Included');
			} else {
				$tmp = [];
				foreach ($b2b_item['lab_result_data']['potency'] as $p) {
					$tmp[ $p['type'] ] = $p;
				}

				$lab_report = [];
				$lab_report[] = $this->_report_info('THC', sprintf('%0.2f %s', __h($tmp['thc']['value']), __h($tmp['thc']['unit'])));
				$lab_report[] = $this->_report_info('THCA', sprintf('%0.2f %s', __h($tmp['thca']['value']), __h($tmp['thca']['unit'])));
				$lab_report[] = $this->_report_info('THC Total', sprintf('%0.2f %s', __h($tmp['total-thc']['value']), __h($tmp['total-thc']['unit'])));
				$lab_report[] = $this->_report_info('CBD', sprintf('%0.2f %s', __h($tmp['cbd']['value']), __h($tmp['cbd']['unit'])));
				$lab_report[] = $this->_report_info('CBDA', sprintf('%0.2f %s', __h($tmp['cbda']['value']), __h($tmp['cbda']['unit'])));
				$lab_report[] = $this->_report_info('CBD Total', sprintf('%0.2f %s', __h($tmp['total-cbd']['value']), __h($tmp['total-cbd']['unit'])));

				ob_start();
				$this->_echo_report($lab_report);
				$tab = ob_get_clean();

				$report[] = $this->_report_info('Lab Result Potency Data', $tab);
			}

			// $v = sprintf('<pre>%s</pre>', __h(json_encode($b2b_item, JSON_PRETTY_PRINT)));
			// $report[] = $this->_report_info('B2B Item Dump', $v);

//     "created_at": "2025-01-30T18:33:19+0000",
//     "updated_at": "2025-02-04T03:43:31+0000",
//     "uom": "ea",
//     "unit_weight": 1.2,
//     "unit_weight_uom": "g",
//     "sample_type": null,
//     "line_price": 255,
//     "is_medical": false,
//     "is_sample": false,
// is_for_extraction

			$this->_echo_report($report);

			echo '</div>';
		}

		// echo '<pre>' . __h(json_encode($doc, JSON_PRETTY_PRINT)) . '</pre>';

	}
}
