#!/usr/bin/env php
<?php
/**
 * OpenTHC Pub Test Runner
 *
 * SPDX-License-Identifier: MIT
 */

require_once(dirname(__DIR__) . '/boot.php');

// Default Option
if (empty($_SERVER['argv'][1])) {
	$_SERVER['argv'][1] = 'phpunit';
	$_SERVER['argc'] = count($_SERVER['argv']);
}

// Command Line
$doc = <<<DOC
OpenTHC Pub Test Runner

Usage:
	test [options]

Options:
	--phpunit-filter=<FILTER>   Some Filter for PHPUnit
DOC;

$res = \Docopt::handle($doc, [
	'exit' => false,
	'optionsFirst' => true,
]);
var_dump($res);
$cli_args = $res->args;
var_dump($cli_args);


// Test Config
$cfg = [];
$cfg['base'] = APP_ROOT;
$cfg['site'] = 'pub';

$test_helper = new \OpenTHC\Test\Helper($cfg);
$cfg['output'] = $test_helper->output_path;


// PHPLint
if ($cli_args['phplint']) {
	$tc = new \OpenTHC\Test\Facade\PHPLint($cfg);
	$res = $tc->execute();
	var_dump($res);
}


// PHPStan
if ($cli_args['phpstan']) {
	$tc = new \OpenTHC\Test\Facade\PHPStan($cfg);
	$res = $tc->execute();
	var_dump($res);
}


// Psalm/Psalter?


// PHPUnit
// Pick Config File
$cfg_file_list = [];
$cfg_file_list[] = sprintf('%s/phpunit.xml', __DIR__);
$cfg_file_list[] = sprintf('%s/phpunit.xml.dist', __DIR__);
foreach ($cfg_file_list as $f) {
	if (is_file($f)) {
			$cfg['--configuration'] = $f;
			break;
	}
}
// Filter?
if ( ! empty($cli_args['--phpunit-filter'])) {
	$cfg['--filter'] = $cli_args['--phpunit-filter'];
}
$tc = new OpenTHC\Test\Facade\PHPUnit($cfg);
$res = $tc->execute();
var_dump($res);


// Output
$res = $test_helper->index_create($res['data']);
echo "TEST COMPLETE\n  $res\n";
