<?php

require 'inc.bootstrap.php';

var_dump($loggedIn = $client->logIn());
if (!$loggedIn) {
	echo "Can't log in.\n";
	exit(1);
}

// print_r($client->getFollows());
print_r($client->getPledges());
// print_r($client->getBills(date('Y')));

print_r($client->_requests);
