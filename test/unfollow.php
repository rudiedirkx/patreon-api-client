<?php

$followId = $_SERVER['argv'][1] ?? null;
if (!$followId) {
	echo "Need follow id\n";
	exit(1);
}

require 'inc.bootstrap.php';

$client->unfollow(new rdx\patreon\Follow(
	$followId,
	new rdx\patreon\Creator('creatorId', 'campaignId', 'name', 'url', 'vanity'),
	1
));

print_r($client->_requests);
