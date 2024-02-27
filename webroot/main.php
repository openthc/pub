<?php
/**
 * OpenTHC Pub Main Entrypoint
 *
 * SPDX-License-Identifier: MIT
 */

header('cache-control: no-store'); //no-cache, must-revalidate');
header('content-language: en');

require_once('../boot.php');

set_error_handler('_eh', (E_ALL & ~ E_NOTICE));
set_exception_handler('_eh');

$cfg = [];
// $cfg['debug'] = true;
$app = new \OpenTHC\App($cfg);

$con = $app->getContainer();
// Clears Slim Handlers
unset($con['errorHandler']);
unset($con['exceptionHandler']);
unset($con['phpErrorHandler']);
$con['notAllowedHandler'] = function() {
	_exit_json([
		'data' => null,
		'meta' => [ 'note' => 'Method Not Allowed [APP-034]' ]
	], 405);

};
$con['notFoundHandler'] = function($c) {
	_exit_json([
		'data' => null,
		'meta' => [ 'note' => 'Not Found [APP-027]' ]
	], 404);
};

// Show everyone my Public Key
$app->get('/pk', function() {
	_exit_text(\OpenTHC\Config::get('pub/public'));
});

// Allow TEST for a Message?
// $app->get('/test', function() {
// 	require_once(__DIR__ . '/test.php');
// });

// $app->get('/b2b/{\w{26}}', OpenTHC\Pub\Controller\B2B\Main);
// $app->get('/b2b/{\w{26}}.html', OpenTHC\Pub\Controller\B2B\Main); // same as above
// $app->get('/b2b/{\w{26}}.pdf', OpenTHC\Pub\Controller\B2B\Main); // Our Document
// $app->get('/b2b/{\w{26}}/manifest.pdf', OpenTHC\Pub\Controller\B2B\Main); // LCB Document
// $app->get('/b2b/{\w{26}}.json', OpenTHC\Pub\Controller\B2B\WCIA);  // Our JSON Output
// $app->get('/b2b/{\w{26}}/wcia.json', OpenTHC\Pub\Controller\B2B\WCIA); // WCIA JSON

// $app->post('/b2b/{\w{26}}', OpenTHC\Pub\Controller\B2B\Create | Update);

// $app->post('/b2b/{\w{26}}/wcia.json', OpenTHC\Pub\Controller\B2B\WCIA);

$app->get('/lab/{id:\w{26}}', 'OpenTHC\Pub\Controller\Lab\Main');
$app->post('/lab/{id:\w{26}}', 'OpenTHC\Pub\Controller\Lab\Main:post');
$app->get('/lab/{id:\w{26}}/coa', 'OpenTHC\Pub\Controller\Lab\COA');
$app->post('/lab/{id:\w{26}}/coa', 'OpenTHC\Pub\Controller\Lab\COA:post');
$app->get('/lab/{id:\w{26}}/coa.{type:html|pdf}', 'OpenTHC\Pub\Controller\Lab\COA');
$app->get('/lab/{id:\w{26}}.json', 'OpenTHC\Pub\Controller\Lab\WCIA'); // Our JSON
$app->get('/lab/{id:\w{26}}/wcia.json', 'OpenTHC\Pub\Controller\Lab\WCIA'); // WCIA JSON
$app->get('/lab/{id:\w{26}}/ccrs.ccv', 'OpenTHC\Pub\Controller\Lab\CCRS'); // CCRS CSV Format

// $app->get('/msg/{.....})


// GET Public Key Information
$app->get('/{pk:[\w\-]{43}}', function($REQ, $RES, $ARG) {

	$pk_origin = $ARG['pk'];

	$dbc = _dbc();
	$PUB = $dbc->fetchRow('SELECT id, link, body, meta FROM account WHERE id = :p0', [ ':p0' => $pk_origin ]);
	unset($PUB['meta']);

	if (empty($PUB['id'])) {
		_exit_html_warn('<h1>Public Endpoint Not Found [APP-040]</h1><p>This public-key-endpoint will need register, see <a href="/#reg">the homepage</a> for instructions.</p>', 404);
	}

	// $PUB['outgoing_message_list'] = [];
	$PUB['incoming_message_list'] = $dbc->fetchAll('SELECT id FROM message WHERE pk_target = :pk', [ ':pk' => $pk_origin ]);

	_exit_json([
		'data' => $PUB,
		'meta' => [],
	]);

});

// GET a Specific Message
$app->get('/{pk:[\w\-]{43}}/{msg:\w{26}}', function($REQ, $RES, $ARG) {

	$pk = $ARG['pk'];

	$dbc = _dbc();
	$PUB = $dbc->fetchRow('SELECT * FROM account WHERE id = :p0', [ ':p0' => $pk ]);
	if (empty($PUB['id'])) {
		_exit_text('Endpoint Not Found [APP-040]', 404);
	}

	$res = $dbc->fetchRow('SELECT * FROM message WHERE id = :m0 AND pk_target = :pk', [
		':m0' => $ARG['msg'],
		':pk' => $pk
	]);
	if (empty($res['id'])) {
		_exit_json([
			'data' => 'Message Not Found [PUB-086]',
			'meta' => []
		], 404);
	}

	_exit_json([
		'data' => [
			'id' => $res['id'],
			'origin' => $res['pk_source'],
			'nonce' => $res['nonce'],
			'crypt' => $res['crypt'],
		]
	]);

});

// DELETE a Specific Message
$app->delete('/{pk:[\w\-]{43}}/{msg:\w{26}}', function($REQ, $RES, $ARG) {

	// Has to be Signed
	$nonce_data = deb64($_GET['n']);
	$crypt_data = deb64($_GET['c']);

	$origin_pk = $ARG['pk']; // Senders Public Key

	$kp1 = sodium_crypto_box_keypair_from_secretkey_and_publickey(deb64(OPENTHC_PUB_SK), deb64($origin_pk));
	$plain_data = sodium_crypto_box_open($crypt_data, $nonce_data, $kp1);
	if (empty($plain_data)) {
		_exit_json([
			'data' => null,
			'meta' => [ 'detail' => 'Operation Not Allowed [PUB-116]' ]
		], 403);
	}

	$delete = json_decode($plain_data, true);

	if ('DELETE' === $delete['action']) {

		$dbc = _dbc();

		$msg = $dbc->fetchRow('SELECT id FROM message WHERE pk_target = :pk0 AND id = :m0', [
			':pk0' => $origin_pk,
			':m0' => $ARG['msg'],
		]);
		if (empty($msg['id'])) {
			_exit_json([
				'data' => null,
				'meta' => [ 'detail' => 'Message Not Found [PUB-137]' ]
			], 404);
		}

		$dbc->query('BEGIN');

		$res = $dbc->query('DELETE FROM message WHERE pk_target = :pk0 AND id = :m0', [
			':pk0' => $origin_pk,
			':m0' => $ARG['msg'],
		]);
		if (1 == $res) {

			$dbc->query('COMMIT');

			_exit_json([
				'data' => $res,
				'meta' => []
			], 200);

		} else {

			$dbc->query('ROLLBACK');

			_exit_json([
				'data' => null,
				'meta' => [ 'detail' => 'Some Conflict [PUB-148]' ]
			], 409);

		}

	}

	_exit_json([
		'data' => null,
		'meta' => [ 'detail' => 'Invalid Request [PUB-142]' ]
	], 400);

	exit(0);

});

// POST|PUT to Public Key -- Update the Profile
$app->map([ 'POST', 'PUT' ], '/{pk:[\w\-]{43}}', function($REQ, $RES, $ARG) {

	$pk_source = $ARG['pk'];

	$dbc = _dbc();
	$chk = $dbc->fetchOne('SELECT id FROM account WHERE id = :p0', [ ':p0' => $pk_source ]);
	if (empty($chk)) {
		// Create this Endpoint for Messages
		$dbc->insert('account', [ 'id' => $pk_source ]);
		_exit_json([
			'data' => $pk_source,
			'meta' => [ 'detail' => 'Public Key Registered' ]
		], 201);
	}

	// Needs to be an encrypted message from them to us
	$input_data = _read_post_input();
	if (empty($input_data)) {
		_exit_text('Invalid Request [PUB-129]', 400);
	}

	// Anonymous Decryption of messages encrypted to me
	$spk = sodium_crypto_box_keypair_from_secretkey_and_publickey(deb64(OPENTHC_PUB_SK), deb64($pk_source));
	$plain_data = sodium_crypto_box_open(deb64($input_data['crypt']), deb64($input_data['nonce']), $spk);
	if (false === $plain_data) {
		_exit_json([
			'data' => 'Invalid Request [PUB-137]',
			'meta' => []
		], 400);
	}

	// The $plain_data should be JSON
	$plain_data = json_decode($plain_data, true);
	if (empty($plain_data)) {
		_exit_json([
			'data' => 'Invalid Request [PUB-137]',
			'meta' => []
		], 400);
	}

	$plain_data['pk'] = $pk_source;

	// Update?
	$update = [];
	if ( ! empty($plain_data['body'])) {
		$update['body'] = $plain_data['body'];
	}
	if ( ! empty($plain_data['link'])) {
		$update['link'] = $plain_data['link'];
	}
	$update['meta'] = json_encode($plain_data, true);

	if ( ! empty($update)) {
		$dbc->update('account', $update, [ 'id' => $pk_source ]);
	}

	_exit_json([
		'data' => $plain_data,
		'meta' => []
	], 200);

});

// POST|PUT to a Specific Target
$app->map(['POST', 'PUT' ], '/{pk:[\w\-]{43}}/{pk_target:[\w\-]{43}}', function($REQ, $RES, $ARG) {

	$pk_source = $ARG['pk'];
	$pk_target = $ARG['pk_target'];

	$RET = [
		'data' => null,
		'meta' => [
			'source' => [],
			'target' => [],
		]
	];

	// Get Body
	$input_data = _read_post_input();

	if (empty($input_data['nonce'])) {
		$RET['meta']['detail'] = 'Invalid Nonce [APP-223]';
		_exit_json($RET, 400);
	}

	if (empty($input_data['crypt'])) {
		$RET['meta']['detail'] = 'Invalid Crypt Data [APP-228]';
		_exit_json($RET, 400);
	}

	$MSG = [
		'id' => _ulid(),
		'pk_source' => $pk_source,
		'pk_target' => $pk_target,
		'nonce' => $input_data['nonce'],
		'crypt' => $input_data['crypt']
	];

	// Write It
	$dbc = _dbc();
	$ck_source = $dbc->fetchOne('SELECT id FROM account WHERE id = :p0', [ ':p0' => $pk_source ]);
	if (empty($ck_source)) {
		// Not a Registered ORIGIN
		// Only a Warning?
		$RET['meta']['source'] = [
			'public' => $pk_source,
			'status' => 404,
		];
	}

	$ck_target = $dbc->fetchOne('SELECT id FROM account WHERE id = :p0', [ ':p0' => $pk_target ]);
	if (empty($ck_target)) {
		$dbc->insert('account', [ 'id' => $pk_target ]);
		$RET['meta']['target'] = [
			'public' => $pk_target,
			'status' => 201,
		];
	}

	$dbc->insert('message', $MSG);

	$RET['data'] = $MSG['id'];

	_exit_json($RET, 201);

});

$app->run();

exit(0);
