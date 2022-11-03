<?php

namespace rdx\patreon;

class Creator {

	public function __construct(
		public string $id,
		public string $name,
		public string $url,
		public ?string $currency = null,
		public ?string $creation = null,
		public ?string $campaignId = null,
	) {}

	static public function fromUserAndCampaign(array $user, array $campaign) : self {
		return new self(
			$user['id'], $user['attributes']['full_name'], $user['attributes']['url'],
			currency: $campaign['attributes']['currency'],
			creation: $campaign['attributes']['creation_name'],
			campaignId: $campaign['id'],
		);
	}

}
