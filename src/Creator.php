<?php

namespace rdx\patreon;

class Creator {

	public function __construct(
		public string $creatorId,
		public string $campaignId,
		public string $creatorName,
		public string $campaignName,
		public string $url,
		public ?string $vanity,
		public ?bool $active = null,
		public ?string $currency = null,
		public ?string $creation = null,
		public ?int $currentPatrons = null,
		public ?float $currentlyPledged = null,
	) {}

	static public function fromUserAndCampaign(array $user, array $campaign) : self {
		$cps = $campaign['attributes']['campaign_pledge_sum'] ?? null;
		return new self(
			$user['id'],
			$campaign['id'],
			$user['attributes']['full_name'],
			$campaign['attributes']['name'],
			$user['attributes']['url'],
			$user['attributes']['vanity'],
			active: $campaign['attributes']['published_at'] != null,
			currency: $campaign['attributes']['currency'],
			creation: $campaign['attributes']['creation_name'],
			currentPatrons: $campaign['attributes']['patron_count'] ?? null,
			currentlyPledged: $cps !== null ? $cps / 100 : null,
		);
	}

}
