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
		$rdb = _rdb();

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

		$x = $doc['manifest_type'];
		if ( ! preg_match('/(delivery|pick\-up|transporter)/', $x)) {
			$report[] = $this->_report_fail('B2B Transfer Type', sprintf('Invalid Type: <strong>%s</strong>', __h($x)));
		}

		// From License
		$good = true;
		$x = $doc['from_license_number'];
		if (empty($x)) {
			$good = false;
			$report[] = $this->_report_fail('Origin License', 'Missing License Number');
		}
		$x = $doc['from_license_name'];
		if (empty($x)) {
			$good = false;
			$report[] = $this->_report_warn('Origin License', 'Missing License Name');
		}
		if ($good) {
			$report[] = $this->_report_info('Origin License', sprintf('<code>%s</code> %s', $doc['from_license_number'], $doc['from_license_name']));
		}

		// To License
		$good = true;
		$x = $doc['to_license_number'];
		if (empty($x)) {
			$good = false;
			$report[] = $this->_report_fail('Target License', 'Missing License Number');
		}
		$x = $doc['to_license_name'];
		if (empty($x)) {
			$good = false;
			$report[] = $this->_report_warn('Target License', 'Missing License Name');
		}
		if ($good) {
			$report[] = $this->_report_info('Target License', sprintf('<code>%s</code> %s', $doc['to_license_number'], $doc['to_license_name']));
		}
		// to_license_type?

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
		}

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

			if ( ! empty($b2b_item['external_id'])) {
				if ($b2b_item['external_id'] != $b2b_item['inventory_id']) {
					$report[] = $this->_report_warn('External ID', 'Does not match Inventory ID');

				}
				$report[] = $this->_report_info('External ID', 'Includes Optional <code>external_id</code>');
			}

			$x = $b2b_item['product_sku'];
			if ( ! empty($x)) {
				$report[] = $this->_report_info('Product SKU', 'Includes Optional <code>product_sku</code>');
			}

			$x1 = $b2b_item['inventory_category'];
			$x2 = $b2b_item['inventory_type'];
			$report[] = $this->_report_info('Product Category / Type', sprintf('%s / %s', __h($x1), __h($x2)));

			$report[] = $this->_report_info('Product Name', __h($b2b_item['product_name']));
			$report[] = $this->_report_info('Variety Name', __h($b2b_item['strain_name']));

			// $x = floatval($b2b_item['qty']);

			// line_price
			if ( ! empty($b2b_item['lab_result_data']['lab_result_id'])) {
				$report[] = $this->_report_info('Lab Result', $b2b_item['lab_result_data']['lab_result_id']);
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

			// Lab Result Link
			$x = $b2b_item['lab_result_link'];
			if ( ! empty($x)) {
				$report[] = $this->_report_info('Lab Result Data Link', sprintf('<a href="?source=%s" target="_blank">%s</a>', __h($x), __h($x)));
			}

			$url = $b2b_item['lab_result_link'];
			$key = sprintf('pub/cache/%s', rawurlencode($url));
			$lab_data = $rdb->get($key);
			$lab_data = json_decode($lab_data, true);
			if (empty($lab_data)) {
				$res = $this->_get($url);
				$lab_data = json_decode($res['data']['body'], true);
				$rdb->set($key, $res['data']['body'], [ 'ex' => 86400 ]);
			}
			if (empty($lab_data) || ! is_array($lab_data)) {
				$report[] = $this->_report_fail('Lab Result Data', 'Failed to Fetch Lab Link');
			} else {
				// $sub_verify = Lab($lab_data);
				// $sub_report = $sub_verify->verify();
			}

			if (empty($b2b_item['lab_result_data']['coa'])) {
				$report[] = $this->_report_warn('Lab Result COA', 'Not Included');
			} else {

				$url = $b2b_item['lab_result_data']['coa'];
				$report[] = $this->_report_info('Lab Result COA', sprintf('<a href="?source=%s" target="_blank">%s</a>', __h($url), __h($url)));

				$key = sprintf('pub/cache/%s', rawurlencode($url));
				$coa_data = $rdb->get($key);
				$coa_data = json_decode($coa_data, true);
				if (empty($coa_data)) {
					$res = $this->_get($url);
					$coa_data = [
						'code' => $res['code'],
						'data' => [
							'body' => substr($res['data']['body'], 0, 8)
						],
						'meta' => [
							'content_type' => $res['meta']['content_type']
						]
					];
					$rdb->set($key, json_encode($coa_data), [ 'ex' => 86400 ]);
				}

				// $t1 = microtime(true);
				switch ($coa_data['code']) {
				case 0:
					$report[] = $this->_report_fail('Lab Result COA Data', 'Connection to Server Failed');
					break;
				case 200:
					switch ($coa_data['meta']['content_type']) {
					case 'application/pdf':
						$buf = substr($coa_data['data']['body'], 0, 8);
						$report[] = $this->_report_good('Lab Result COA Data', sprintf('Valid PDF Type: %s', __h($buf)));
						break;
					default:
						$report[] = $this->_report_warn('Lab Result COA Data', sprintf('Invalid HTTP Content Type %s', $coa_data['meta']['content_type']));
					}
					// OK
					break;
				default:
					$report[] = $this->_report_warn('Lab Result COA Data', sprintf('Invalid HTTP Response Code %d', $coa_data['code']));
					break;
				}
			}

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

			$this->_echo_report($report);

			echo '</div>';
		}

	}
}
