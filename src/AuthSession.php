<?php

namespace rdx\patreon;

use GuzzleHttp\Cookie\CookieJar;

class AuthSession implements Auth {

	protected CookieJar $cookies;

	public function __construct( string $session_id ) {
		$this->cookies = new CookieJar(false, [
			[
				'Domain' => '.patreon.com',
				'Name' => 'session_id',
				'Value' => $session_id,
			],
		]);
	}

	public function getCookieJar() : CookieJar {
		return $this->cookies;
	}

	public function logIn(Client $client) : bool {
		return true;
	}

}
