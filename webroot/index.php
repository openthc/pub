<?php
/**
 * OpenTHC Pub Homepage
 *
 * SPDX-License-Identifier: MIT
 */

$host = $_SERVER['SERVER_NAME'];

?>
<!DOCTYPE html>
<html lang="en-us">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#069420">
<title>OpenTHC :: Pub</title>
<style>
#img-wrap {
	margin: 2vh auto;
	padding: 0;
	text-align:center;
}
a {
	color: #247420;
	font-weight: bold;
}
body {
	background: #101010;
	color: #fdfdfd;
	font-family:sans-serif;
	margin: 0;
	padding: 0;
}
footer {
	margin: 10vh 2vw 0 2vw;
}
header h1, header h2 {
	text-align: center;
}
main {
	margin: 0vh 2vw;
}
pre {
	background: #ccc;
	color: #000;
	margin: 0;
	padding: 0.5rem;
}
</style>
</head>
<body>

<header>
	<div id="img-wrap">
		<img src="https://cdn.openthc.com/img/icon.png">
	</div>
	<h1>Pub</h1>
	<h2>Data Publishing Service provided by OpenTHC</h2>
</header>

<hr>

<main>


<h2>Create Message Endpoint</h2>
<p>Just confirm/register this endpoint and let others know it exists.</p>
<pre>
curl -X POST https://<?= $host ?>/ORIGIN_PUBLIC_KEY
</pre>

<h2>Send Message to Endpoint</h2>
<p>
Just encrypt the message to the public key, then POST.
The body is saved AS-IS and the indicated content-type is trusted.
Typically the contents are encrypted.
</p>

<pre>
curl -X POST https://<?= $host ?>/ORIGIN_PUBLIC_KEY/TARGET_PUBLIC_KEY
</pre>

<h2>Check Messages</h2>
<p>Simply call a `GET` request to your endpoint for a list of messages.</p>
<pre>
curl https://<?= $host ?>/ORIGIN_PUBLIC_KEY
</pre>

</main>

<footer>
	Powered by <a href="https://openthc.com/">OpenTHC</a>
	see <a href="https://github.com/openthc/pub">github.com/openthc/pub</a> for more information
</footer>

</body>
</html>
