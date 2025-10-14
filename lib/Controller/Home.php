<?php
/**
 * Homepage
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Pub\Controller;

// use OpenTHC\Sodium;

class Home extends Base
{
	/**
	 *
	 */
	function __invoke($REQ, $RES, $ARG)
	{
		return $RES->write($this->render());
	}

	/***
	 *
	 */
	function render($file=null, $data=null)
	{
		$origin = \OpenTHC\Config::get('openthc/pub/origin');
		// $origin = sprintf('https://%s', $_SERVER['SERVER_NAME']);

		$readme_text = file_get_contents(APP_ROOT . '/README.md');
		$readme_html = _markdown($readme_text);
		$readme_html = preg_replace('/<h1>.+?<\/h1>/', '', $readme_html);
		$readme_html = str_replace('$SERVER', $origin, $readme_html);

		// sodium_crypto_box_keypair();
		// sodium_crypto_box_publickey($message_kp)
		// $message_pk = random_bytes(16);
		// $message_pk = \OpenTHC\Sodium::b64encode($message_pk);
		// $message_pk = sprintf('<strong style>%s</strong>', $message_pk);

		// $box_sk = sodium_crypto_box_secretkey($box_kp);
		// $readme_html = str_replace('$MESSAGE_PK', $message_pk, $readme_html);

		// $profile_kp = sodium_crypto_box_keypair();
		// $profile_pk = \OpenTHC\Sodium::b64encode(sodium_crypto_box_publickey($profile_kp));
		// $profile_pk = random_bytes(16);
		// $profile_pk = \OpenTHC\Sodium::b64encode($profile_pk);
		// $readme_html = str_replace('$PROFILE_PK', $profile_pk, $readme_html);

		return <<<HTML
		<!DOCTYPE html>
		<html lang="en-us">
		<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="theme-color" content="#069420">
		<link rel="stylesheet" href="/vendor/bootstrap/bootstrap.min.css">
		<title>OpenTHC :: Pub</title>
		<style>
		#img-wrap {
			margin: 2vh auto;
			padding: 0;
			text-align:center;
		}
		a {
			color: #247420;
		}
		footer {
			margin: 12vh 2vw 0 2vw;
		}
		header h1, header h2 {
			text-align: center;
		}
		main {
			margin: 0vh auto;
			max-width: 80vw;
		}
		section {
			margin: 0 0 1vh 0;
		}
		pre {
			background: #333;
			color: #eee;
			padding: 0.25rem;
		}
		</style>
		</head>
		<body>

		<header>
			<div id="img-wrap">
				<img src="https://openthc.com/img/icon.png">
			</div>
			<h1>Pub</h1>
			<h2>Data Publishing/Sharing Service provided by OpenTHC</h2>
		</header>

		<hr>

		<main>

		<section>
			$readme_html
		</section>

		</main>

		<footer>
			Powered by <a href="https://openthc.com/">OpenTHC</a>
			see <a href="https://github.com/openthc/pub">github.com/openthc/pub</a> for more information
		</footer>

		</body>
		</html>
		HTML;
	}

}
