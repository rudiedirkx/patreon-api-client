<?php

$campaignId = $_SERVER['argv'][1] ?? null;
if (!$campaignId) {
	echo "Need campaign id\n";
	exit(1);
}

require 'inc.bootstrap.php';

$follow = $client->follow(new rdx\patreon\Creator('creatorId', $campaignId, 'name', 'url', 'vanity'));
print_r($follow);

print_r($client->_requests);
