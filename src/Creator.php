<?php

namespace rdx\patreon;

class Creator {

	public function __construct(
		public string $id,
		public string $name,
		public string $url,
		public string $creation,
	) {}

	static public function fromUserAndCampaign(array $user, array $campaign) {
		return new self($user['id'], $user['attributes']['full_name'], $user['attributes']['url'], $campaign['attributes']['creation_name']);
	}

}
