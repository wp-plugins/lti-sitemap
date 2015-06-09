<!DOCTYPE html>
<html>
<head>
	<style type="text/css">
		html {
			background: #ffffff;
		}

		body {
			position: relative;
			color: #444;
			font-family: "Open Sans", sans-serif;
			font-size: 13px;
			line-height: 1.4em;
			z-index: 1;
		}

		.content-wrapper {
			position: relative;
			min-height: 500px;
			margin: 0 auto;
			padding: 0 2em 0;
		}

		h2 {
			font-size: 23px;
			font-weight: 400;
			line-height: 29px;
		}

		.content-wrapper::after {
			position: absolute;
			content: "";
			top: 10%;
			left: 25%;
			width: 400px;
			height: 400px;
			background-image: url("data:image/svg+xml;base64, PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz4NCjxzdmcgdmVyc2lvbj0iMS4xIiBpZD0iTGF5ZXJfMiIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4PSIwcHgiIHk9IjBweCIgdmlld0JveD0iMCAwIDE1My4xIDE5MS40IiBlbmFibGUtYmFja2dyb3VuZD0ibmV3IDAgMCAxNTMuMSAxOTEuNCIgeG1sOnNwYWNlPSJwcmVzZXJ2ZSI+DQo8cGF0aCBpZD0iQmluZyIgZmlsbD0iI0Y0QkQyNyIgZD0iTTQzLjYsMTkxLjRMMCwxNjAuOVYwIGw0My44LDEzLjR2MTA4LjJMMS4zLDE1OS41bDEwMy45LTU0LjdsLTI4LjgtMTNMNTYuOSw0OS42bDk2LjMsMjkuNXY0N0w0My42LDE5MS40eiIvPg0KPC9zdmc+");
			background-repeat: no-repeat;
			opacity: 0.1;
			z-index: -1;
		}

		#wpfooter {
			bottom: 0;
			color: #777;
			left: 0;
			padding-bottom: 10px;
			padding-left: 20px;
			padding-right: 20px;
			padding-top: 10px;
			position: absolute;
			right: 0;
			margin: 0;
		}

		a, div {
			outline-color: -moz-use-text-color;
			outline-style: none;
			outline-width: 0;
		}

		#wpfooter p {
			font-size: 13px;
			line-height: 20px;
			margin-bottom: 0;
			margin-left: 0;
			margin-right: 0;
			margin-top: 0;
		}

		.alignleft {
			float: left;
		}

		#footer-thankyou {
			font-style: italic;
		}

		.alignright {
			float: right;
		}

		.error {
			font-weight: bold;
			color: #c90000;
		}

		.success {
			font-weight: bold;
			color: #1b984c;
		}

		.close {
			width: 50%;
			margin: 150px;
			auto 0;
			text-align: center;
		}

		a {
			text-decoration: underline;
			color: #555555;
		}
	</style>
</head>
<body>
<div>
	<div>
		<div class="content-wrapper">
			<div class="content">
				<h2><?php echo lsmint( 'msg.bing.sending' ); ?>
<?php

$url = filter_input( INPUT_GET, 'bing_url' );
if ( $url === false || is_null( $url ) ) {
	//No URL, no sending
	die( lsmint( 'err.bing.popup' ) );
}

//Doing a simple CURL request
$result = \Lti\Sitemap\Helpers\Bing_Helper::http_request( $url );

if ( array_key_exists( 'http_code', $result ) ) {
	$code = $result['http_code'];

	if ( $code != '200' ) {
		echo sprintf( '<h2 class="error">%s</h2>', lsmint( 'err.bing.not_sent' ) );
	} else {
		echo sprintf( '<h2 class="success">%s</h2>', lsmint( 'msg.bing.sent' ) );
	}
}

echo <<<HTML
<h1 class="close"><a href="#" onclick="window.close();">CLOSE</a></h1>
HTML;
flush();