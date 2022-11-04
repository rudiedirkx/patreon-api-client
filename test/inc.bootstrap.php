<?php

use rdx\patreon\AuthSession;
use rdx\patreon\AuthWeb;
use rdx\patreon\Client;

require 'vendor/autoload.php';
require 'env.php';

// $client = new Client(new AuthWeb(PATREON_USER, PATREON_PASS));
$client = new Client(new AuthSession(PATREON_SESSION_ID));

var_dump($loggedIn = $client->logIn());
if (!$loggedIn) {
	echo "Can't log in.\n";
	exit(1);
}
