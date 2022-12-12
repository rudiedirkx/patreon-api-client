<?php

use rdx\patreon\AuthSession;
use rdx\patreon\AuthWeb;
use rdx\patreon\Client;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/env.php';

function html($text) {
	return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
}

// $client = new Client(new AuthWeb(PATREON_USER, PATREON_PASS));
$client = new Client(new AuthSession(PATREON_SESSION_ID));

var_dump($loggedIn = $client->logIn());
if (!$loggedIn) {
	echo "Can't log in.\n";
	exit(1);
}
