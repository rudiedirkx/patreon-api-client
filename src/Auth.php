<?php

namespace rdx\patreon;

use GuzzleHttp\Cookie\CookieJar;

interface Auth {

	public function getCookieJar() : CookieJar;

	public function logIn(Client $client) : bool;

}
