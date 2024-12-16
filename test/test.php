#!/usr/bin/php
<?php
/**
 * OpenTHC Pub Test
 */

require_once(dirname(__DIR__) . '/boot.php');

// $arg = \OpenTHC\Docopt::parse($doc, ?$argv=[]);
// Parse CLI
$doc = <<<DOC
OpenTHC Pub Test

Usage:
	test [options]

Options:
	--phpunit-filter=<FILTER>   Some Filter for PHPUnit

DOC;

$arg = Docopt::handle($doc, [
	'exit' => false,
	'help' => false,
	'optionsFirst' => true,
]);
$cli_args = $arg->args;
var_dump($cli_args);


define('OPENTHC_TEST_OUTPUT_BASE', \OpenTHC\Test\Helper::output_path_init());


// Call Linter?
$tc = new \OpenTHC\Test\Facade\PHPLint([
	'output' => OPENTHC_TEST_OUTPUT_BASE
]);
// $res = $tc->execute();
// var_dump($res);

#
# PHP-CPD
# vendor/openthc/common/test/phpcpd.sh
// vendor/bin/phpmd boot.php,webroot/main.php,lib/,test/ \
// 	html \
// 	cleancode \
// 	--report-file "${OUTPUT_BASE}/phpmd.html" \
// 	|| true

// Call PHPCS?
// $tc = \OpenTHC\Test\PHPStyle::execute();


// PHPStan
$tc = new OpenTHC\Test\Facade\PHPStan([
	'output' => OPENTHC_TEST_OUTPUT_BASE
]);
// $res = $tc->execute();
// var_dump($res);


// Psalm/Psalter?


// PHPUnit
$cfg = [
	'output' => OPENTHC_TEST_OUTPUT_BASE
];
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

// Done
\OpenTHC\Test\Helper::index_create($html);

// Output Information
$origin = \OpenTHC\Config::get('openthc/pub/origin');
$output = str_replace(sprintf('%s/webroot/', APP_ROOT), '', OPENTHC_TEST_OUTPUT_BASE);

echo "TEST COMPLETE\n  $origin/$output\n";
