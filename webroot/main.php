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
$cfg['debug'] = false;
$app = new \OpenTHC\App($cfg);

// Show everyone my Public Key
$app->get('/pk', function() {
	_exit_text(\OpenTHC\Config::get('openthc/pub/public'));
});

// Profile?
// Return a List of Objects under this PK if authorized
// or return info about this registered PK (maybe)
$app->get('/{pk:[\w\-]{43}}', 'OpenTHC\Pub\Controller\Profile');

// Update this Public Profile for this PK
// POST JSON and it's encrypted, with the SK for the indicated PK
$app->map(['POST','PUT'], '/{pk:[\w\-]{43}}', 'OpenTHC\Pub\Controller\Profile:update');
// $app->map(['POST','PUT'], '/{pk:[\w\-]{43}}/{path:[\w\-\.]{4,64}}', 'OpenTHC\Pub\Controller\Profile:put');
// $app->delete('/p/{pk:[\w\-]{43}}/{path:[\w\-\.]{4,64}}', 'OpenTHC\Pub\Controller\Profile:del');


// Message
$message_path = '/{pk:[\w\-]{43}}/{path:[\w\-\.]{4,64}}';
$app->get($message_path, 'OpenTHC\Pub\Controller\Message:get');
$app->map(['POST','PUT'], $message_path, 'OpenTHC\Pub\Controller\Message:put');
$app->delete($message_path, 'OpenTHC\Pub\Controller\Message:del');

$app->run();

exit(0);
