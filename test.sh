#!/bin/bash -x
#
# OpenTHC Test Runner
#

set -o errexit
set -o nounset

x=${OPENTHC_TEST_BASE:-}
if [ -z "$x" ]
then
	echo "You have to define the environment first"
	exit 1
fi

f=$(readlink -f "$0")
d=$(dirname "$f")

cd "$d"

output_base="webroot/test-output"
output_main="$output_base/index.html"
mkdir -p "$output_base"

code_list=(
	boot.php lib/ test/
)


#
# Lint
if [ ! -f "$output_base/phplint.txt" ]
then

	echo '<h1>Linting...</h1>' > "$output_main"

	find "${code_list[@]}" -type f -name '*.php' -exec php -l {} \; \
		| grep -v 'No syntax' || true \
		2>&1 \
		>"$output_base/phplint.txt"

	[ -s "$output_base/phplint.txt" ] || echo "Linting OK" >"$output_base/phplint.txt"

fi


#
# PHP-CPD
if [ ! -f "$output_base/phpcpd.txt" ]
then

	echo '<h1>CPD Check</h1>' > "$output_main"

	vendor/bin/phpcpd \
		--fuzzy \
		"${code_list[@]}" \
		2>&1 \
		> "$output_base/phpcpd.txt" \
		|| true

fi


#
# PHPStan
if [ ! -f "$output_base/phpstan.html" ]
then

	xsl_file="test/phpstan.xsl"

	echo '<h1>PHPStan...</h1>' > "$output_main"

	vendor/bin/phpstan \
		analyze \
		--configuration=test/phpstan.neon \
		--error-format=junit \
		--level=2 \
		--no-ansi \
		--no-progress \
		"${code_list[@]}" \
		> "$output_base/phpstan.xml" \
		|| true

	[ -f "$xsl_file" ] || curl -qs 'https://openthc.com/pub/phpstan.xsl' > "$xsl_file"

	xsltproc \
		--nomkdir \
		--output "$output_base/phpstan.html" \
		"$xsl_file" \
		"$output_base/phpstan.xml"

fi


#
# PHPUnit
echo '<h1>PHPUnit...</h1>' > "$output_main"
vendor/bin/phpunit \
	--configuration "test/phpunit.xml" \
	--log-junit "$output_base/phpunit.xml" \
	--testdox-html "$output_base/testdox.html" \
	--testdox-text "$output_base/testdox.txt" \
	--testdox-xml "$output_base/testdox.xml" \
	--verbose \
	test/ \
	"$@" \
	2>&1 | tee "$output_base/phpunit.txt"


#
# Transform
echo '<h1>Transforming...</h1>' > "$output_main"
[ -f "test/phpunit-report.xsl" ] || curl -qs 'https://openthc.com/pub/phpunit/report.xsl' > "test/phpunit-report.xsl"
xsltproc \
	--nomkdir \
	--output "$output_base/phpunit.html" \
	"test/phpunit-report.xsl" \
	"$output_base/phpunit.xml"


#
# Final Output
dt=$(date)
note=$(tail -n1 "$output_base/phpunit.txt")

cat <<HTML > "$output_main"
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="initial-scale=1, user-scalable=yes">
<meta name="theme-color" content="#069420">
<style>
html {
	font-family: sans-serif;
	font-size: 1.5rem;
}
</style>
<title>Test Result ${dt}</title>
</head>
<body>

<h1>Test Result ${dt}</h1>
<h2>${note}</h2>

<p>Linting: <a href="phplint.txt">phplint.txt</a></p>
<p>PHPCPD: <a href="phpcpd.txt">phpcpd.txt</a></p>
<p>PHPStan: <a href="phpstan.xml">phpstan.xml</a> and <a href="phpstan.html">phpstan.html</a></p>
<p>PHPUnit: <a href="phpunit.txt">phpunit.txt</a>, <a href="phpunit.xml">phpunit.xml</a> and <a href="phpunit.html">phpunit.html</a></p>
<p>Textdox: <a href="testdox.txt">testdox.txt</a>, <a href="testdox.xml">testdox.xml</a> and <a href="testdox.html">testdox.html</a></p>

</body>
</html>
HTML
