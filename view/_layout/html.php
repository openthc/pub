<?php
/**
 * Pub Layout
 *
 * SPDX-License-Identifier: MIT
 */
?>
<!DOCTYPE html>
<html translate="no">
<head>
<meta charset="utf-8">
<meta name="application-name" content="OpenTHC Pub">
<meta name="mobile-web-app-capable" content="yes">
<meta name="google" content="notranslate">
<meta name="theme-color" content="#069420">
<meta name="viewport" content="initial-scale=1, user-scalable=yes">
<link rel="stylesheet" href="/vendor/fontawesome/css/all.min.css">
<link rel="stylesheet" href="/vendor/bootstrap/bootstrap.min.css">
<title><?= __h($this->data['page_title']) ?></title>
<style>
/**
	Footer Stuff
*/
footer {
	background: #000700;
	border-top: 6px solid #192;
	font-size: 130%;
	margin-top: 8em;
	padding: 1em 0 0 0;
}
footer a {
	color: #fff;
}
footer ul {
	list-style-type: none;
	margin: 1em;
}
footer ul li {
	padding: 0.25em;
}

</style>
</head>
<body>

<nav class="navbar navbar-expand-md navbar-dark bg-dark sticky-top">
<div class="container-fluid">

	<a class="navbar-brand" href="/"><i class="fa-solid fa-house"></i></a>

	<button type="button" class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#ot-menu-head" aria-expanded="false" aria-controls="ot-menu-head">
		<span class="navbar-toggler-icon"></span>
	</button>
<!--
	<div class="navbar-collapse collapse" id="ot-menu-head">
		<ul class="navbar-nav mr-auto">
			<li class="nav-item">
				<a class="nav-link" href="/data-model"><i class="fa-solid fa-database"></i> Data Model</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" href="/json-schema"><i class="fa-solid fa-sitemap"></i> JSON Schema</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" href="/json-example"><i class="fa-solid fa-wand-magic-sparkles"></i> JSON Examples</a>
			</li>
		</ul>
	</div>
 -->
</div>
</nav>

<main style="min-height: 80vh;">
<?= $this->body ?>
</main>

<div class="main-foot">
	<footer>
		<div><a href="https://openthc.com/">openthc.com</a></div>
		<div><a href="https://openthc.com/about/tos">Terms of Service</a></div>
		<div><a href="https://openthc.com/about/privacy">Privacy</a></div>
	</footer>
</div>

<script src="/vendor/jquery/jquery.min.js"></script>
<script src="/vendor/bootstrap/bootstrap.bundle.min.js"></script>
<script src="/vendor/htmx/htmx.min.js"></script>

<?= $this->foot_script ?>

</body>
</html>
