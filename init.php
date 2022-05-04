#!/usr/bin/php
<?php
/**
 * Initialise the System
 *
 * SPDX-License-Identifier: MIT
 */

$config_file = sprintf('%s/etc/config.php', __DIR__);
if (is_file($config_file)) {
	echo "Already Configured\n";
	exit(1);
}

touch($config_file);

require_once(__DIR__ . '/boot.php');

$raw = sodium_crypto_box_keypair();
$pk0 = sodium_crypto_box_publickey($raw);
$sk0 = sodium_crypto_box_secretkey($raw);
$pk0b64 = enb64($pk0);
$sk0b64 = enb64($sk0);


$config_text = <<<TXT
<?php
/**
 * OpenTHC Pub Configuration
 */

\$ret = [];

\$ret['app'] = [
	'base' => '$argv[1]'
];

\$ret['pub'] = [
	'public' => '$pk0b64',
	'secret' => '$sk0b64',
];

return \$ret;
TXT;

file_put_contents($config_file, $config_text);

$dbc = new \Edoceo\Radix\DB\SQL(sprintf('sqlite:%s/var/pub.sqlite', __DIR__));
$dbc->query('CREATE TABLE account (id PRIMARY KEY, created_at TEXT DEFAULT CURRENT_TIMESTAMP, link, body, meta)');
$dbc->insert('account', [
	'id' => $pk0b64,
	'link' => '/pk',
	'body' => 'This is the account for the Site Operator',
]);

$dbc->query('CREATE TABLE message (id PRIMARY KEY, created_at TEXT DEFAULT CURRENT_TIMESTAMP, pk_source, pk_target, nonce, crypt)');

# sqlite3 $FILE <EOF
# CREATE TRIGGER update_at_now BEFORE INSERT UPDATE ON account
# BEGIN
# 	NEW.updated_at = now();
# END;
# EOF

# sqlite3 $FILE <EOF
# CREATE TRIGGER update_at_now BEFORE INSERT UPDATE ON message
# BEGIN
# 	NEW.updated_at = now();
# END;
# EOF

chown(sprintf('%s/var/pub.sqlite', __DIR__), 'www-data');
chgrp(sprintf('%s/var/pub.sqlite', __DIR__), 'www-data');
