<?php

namespace rdx\patreon;

use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\RedirectMiddleware;
use rdx\jsdom\Node;

class Client {

	protected Auth $auth;
	protected Guzzle $guzzle;

	public ?string $accountId = null;
	public ?string $accountEmail = null;
	public ?string $accountCurrency = null;

	public ?array $creators = null;
	public ?array $follows = null;
	public ?array $pledges = null;

	public function __construct(Auth $auth) {
		$this->auth = $auth;

		$this->guzzle = new Guzzle([
			'http_errors' => false,
			'cookies' => $auth->getCookieJar(),
			'allow_redirects' => [
				'track_redirects' => true,
			] + RedirectMiddleware::$defaultSettings,
		]);
	}

	public function getFollows() : array {
		if ($this->follows === null) {
			$this->fetchFollowsAndPledges();
		}
		return $this->follows;
	}

	public function getPledges() : array {
		if ($this->follows === null) {
			$this->fetchFollowsAndPledges();
		}
		return $this->pledges;
	}

	protected function fetchFollowsAndPledges() : void {
		$rsp = $this->guzzle->get('https://www.patreon.com/api/current_user?include=pledges.creator.campaign.null,pledges.campaign.null,follows.followed.campaign.null&fields[user]=full_name,url&fields[campaign]=creation_name,pay_per_name,is_monthly,is_nsfw,name,url&fields[pledge]=amount_cents,cadence&fields[follow]=created_at&json-api-version=1.0');
		$json = (string) $rsp->getBody();
		$data = json_decode($json, true);
// print_r($data);

		$index = []; // pledge, follow, campaign, user
		foreach ($data['included'] as $object) {
			$index[ $object['type'] ][ $object['id'] ] = $object;
		}

		$this->creators = array_map(function($user) use ($index) {
			$campaignId = $user['relationships']['campaign']['data']['id'];
			$campaign = $index['campaign'][$campaignId];
			return Creator::fromUserAndCampaign($user, $campaign);
		}, $index['user']);

		$this->follows = [];
		foreach ($data['data']['relationships']['follows']['data'] as $meta) {
			$follow = $index['follow'][ $meta['id'] ];
			$rel = $follow['relationships']['followed']['data'];
			if ($rel['type'] == 'user' && isset($this->creators[ $rel['id'] ])) {
				$this->follows[ $rel['id'] ] = Follow::fromCreatorAndFollow($this->creators[ $rel['id'] ], $follow);
			}
		}

		$this->pledges = [];
		foreach ($data['data']['relationships']['pledges']['data'] as $meta) {
			$pledge = $index['pledge'][ $meta['id'] ];
			$rel = $pledge['relationships']['creator']['data'];
			if ($rel['type'] == 'user' && isset($this->creators[ $rel['id'] ])) {
				$this->pledges[ $rel['id'] ] = Pledge::fromCreatorAndPledge($this->creators[ $rel['id'] ], $pledge);
			}
		}
	}

	public function logIn() : bool {
		return $this->auth->logIn($this) && $this->checkSession();
	}

	protected function checkSession() : bool {
		$rsp = $this->guzzle->get('https://www.patreon.com/api/current_user?json-api-version=1.0');

		if ($rsp->getStatusCode() != 200) {
			return false;
		}

		$redirects = $rsp->getHeader(RedirectMiddleware::HISTORY_HEADER);
		if (count($redirects)) {
			return false;
		}

		$json = (string) $rsp->getBody();
		$data = json_decode($json, true);
		if (!is_array($data) || !isset($data['data']['id'], $data['data']['attributes']['email'])) {
			return false;
		}

		$this->accountId = $data['data']['id'];
		$this->accountEmail = $data['data']['attributes']['email'];
		$this->accountCurrency = $data['data']['attributes']['patron_currency'];

		return true;
	}

}
