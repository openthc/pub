<?php
/**
 * WCIA Validator Base
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Pub\WCIA\Validator;

class B2B extends Base
{
	function validate()
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

		foreach ($doc['inventory_transfer_items'] as $idx => $b2b_item) {

			$report_key = sprintf('Inventory Item %d', $idx + 1);

			$x = $b2b_item['inventory_id'];
			if (empty($x)) {
				$report[] = $this->_report_fail($report_key, 'Missing Inventory ID');
			} else {
				$report[] = $this->_report_info($report_key, __h($x));
			}

			$x = $b2b_item['external_id'];
			if ( ! empty($x)) {
				$report[] = $this->_report_info($report_key, 'Includes Optional <code>external_id</code>');
			}

			$x = $b2b_item['product_sku'];
			if ( ! empty($x)) {
				$report[] = $this->_report_info($report_key, 'Includes Optional <code>product_sku</code>');
			}

			$x = $b2b_item['inventory_category'];
			$report[] = $this->_report_info($report_key, sprintf('Product Category: %s', __h($x)));

			$x = $b2b_item['inventory_type'];
			$report[] = $this->_report_info($report_key, sprintf('Product Type: %s', __h($x)));

			// $x = $b2b_item['product_name'];

			// $x = $b2b_item['strain_name'];

			// $x = floatval($b2b_item['qty']);

			// line_price

			$x = $b2b_item['lab_result_passed'];
			$report[] = $this->_report_info($report_key, sprintf('Lab Status: <strong>%s</strong>', __h($x)));

			$x = $b2b_item['lab_result_link'];
			if ( ! empty($x)) {
				$report[] = $this->_report_info($report_key, 'Has Lab Result Link');
			}

			// echo '<pre>';
			// echo __h(json_encode($b2b_item, JSON_PRETTY_PRINT));
			// echo '</pre>';

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
//     "lab_result_link": "https:\/\/openthc.pub\/-xwDgQAUpDI-u4cL6AAuNV2dsJSziOO2cjZxP1IiWzs\/wcia.json",
//     "lab_result_passed": "pass",
//     "lab_result_data": {
//         "lab_result_id": "1165875",
//         "lab_result_status": "pass",
//         "coa": null,
//         "potency": [
//             {
//                 "type": "cbd",
//                 "unit": "pct",
//                 "value": "0.6500"
//             },
//             {
//                 "type": "cbda",
//                 "unit": "pct",
//                 "value": "0.0000"
//             },
//             {
//                 "type": "total-cbd",
//                 "unit": "pct",
//                 "value": "0.6500"
//             },
//             {
//                 "type": "thc",
//                 "unit": "pct",
//                 "value": "1.9000"
//             },
//             {
//                 "type": "thca",
//                 "unit": "pct",
//                 "value": "29.0000"
//             },
//             {
//                 "type": "total-thc",
//                 "unit": "pct",
//                 "value": "27.0000"
//             }
//         ]
//     }
		}

		$this->_echo_report($report);

		// echo '<pre>' . __h(json_encode($doc, JSON_PRETTY_PRINT)) . '</pre>';

	}
}
