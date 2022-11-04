<?php

require 'inc.bootstrap.php';

print_r($client->getBills(date('Y')));

print_r($client->_requests);
