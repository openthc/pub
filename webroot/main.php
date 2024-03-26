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
$cfg['debug'] = true;
$app = new \OpenTHC\App($cfg);

$app->get('/home', 'OpenTHC\Pub\Controller\Home');

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
$app->delete('/{pk:[\w\-]{43}}', 'OpenTHC\Pub\Controller\Profile:delete');

// Profile Messages
$profile_message_path = '/{pk:[\w\-]{43}}/{message_pk:[\w\-]{43}}/{path:[\w\-\.]{4,64}}';
$app->get($profile_message_path, 'OpenTHC\Pub\Controller\Profile:message_get');
$app->map(['POST','PUT'], $profile_message_path, 'OpenTHC\Pub\Controller\Profile:message_put');
$app->delete($profile_message_path, 'OpenTHC\Pub\Controller\Profile:message_del');


// Message
$message_path = '/{pk:[\w\-]{43}}/{path:[\w\-\.]{4,64}}';
$app->get($message_path, 'OpenTHC\Pub\Controller\Message:get');
$app->map(['POST','PUT'], $message_path, 'OpenTHC\Pub\Controller\Message:put');
$app->delete($message_path, 'OpenTHC\Pub\Controller\Message:del');

$app->run();

exit(0);
