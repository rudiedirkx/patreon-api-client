<?php

namespace rdx\patreon;

use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RedirectMiddleware;
use RuntimeException;
use rdx\jsdom\Node;

class Client {

	static public $fetchFields = [
		'user' => 'full_name,vanity,url',
		'campaign' => 'name,creation_name,pay_per_name,currency,patron_count,campaign_pledge_sum,published_at',
		'bill' => 'amount_cents,created_at,currency',
		'pledge' => 'amount_cents,created_at',
		'follow' => 'created_at',
	];

	protected Auth $auth;
	protected Guzzle $guzzle;
	public array $_requests = [];

	public ?string $accountId = null;
	public ?string $accountEmail = null;
	public ?string $accountCurrency = null;

	protected ?string $csrfToken = null;

	public array $creators = [];
	public array $billableYears = [];

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

	public function follow(Creator $creator) : Follow {
		$rsp = $this->post('https://www.patreon.com/api/follow?include=campaign.current_user_follow.null&fields[campaign]=id&fields[follow]=' . self::$fetchFields['follow'] . '&json-api-use-default-includes=false&json-api-version=1.0', [
			'data' => [
				'type' => 'follow',
				'attributes' => (object) [],
				'relationships' => [
					'follower' => [
						'data' => [
							'type' => 'user',
							'id' => $this->accountId,
						],
					],
					'campaign' => [
						'data' => [
							'type' => 'campaign',
							'id' => $creator->campaignId,
						],
					],
				],
			],
		]);
		$json = (string) $rsp->getBody();
		$data = json_decode($json, true);
// print_r($data);

		if (!empty($data['errors'])) {
			throw new RuntimeException($data['errors'][0]['detail']);
		}

		return Follow::fromCreatorAndFollow($creator, $data['data']);
	}

	public function unfollow(Follow $follow) : void {
		$rsp = $this->delete("https://www.patreon.com/api/follow/$follow->id");
		$json = (string) $rsp->getBody();

		if (!empty($data['errors'])) {
			throw new RuntimeException($data['errors'][0]['detail']);
		}
	}

	public function getFollows() : array {
		$data = $this->getJson('https://www.patreon.com/api/current_user', [
			'include' => 'follows.followed.campaign.null,follows.followed.creator.null',
			'fields' => [
				'user' => self::$fetchFields['user'],
				'campaign' => self::$fetchFields['campaign'],
				'follow' => self::$fetchFields['follow'],
			],
			'json-api-version' => '1.0',
		]);

		$map = $this->mapIncluded($data['included']);
		$this->persistCreatorsFromMappedUsers($map);

		$follows = [];
		foreach ($data['data']['relationships']['follows']['data'] as $meta) {
			$follow = $map['follow'][ $meta['id'] ];
			$rel = $follow['relationships']['followed']['data'];
			if ($rel['type'] == 'user' && isset($this->creators[ $rel['id'] ])) {
				$follow = Follow::fromCreatorAndFollow($this->creators[ $rel['id'] ], $follow);
				$follows[$follow->creator->creatorId] = $follow;
			}
		}

		return $follows;
	}

	public function getPledges() : array {
		$data = $this->getJson('https://www.patreon.com/api/current_user', [
			'include' => 'pledges.creator.campaign.null,pledges.campaign.null',
			'fields' => [
				'user' => self::$fetchFields['user'],
				'campaign' => self::$fetchFields['campaign'],
				'pledge' => self::$fetchFields['pledge'],
			],
			'json-api-version' => '1.0',
		]);

		$map = $this->mapIncluded($data['included']);
		$this->persistCreatorsFromMappedUsers($map);

		$pledges = [];
		foreach ($data['data']['relationships']['pledges']['data'] as $meta) {
			$pledge = $map['pledge'][ $meta['id'] ];
			$rel = $pledge['relationships']['creator']['data'];
			if ($rel['type'] == 'user' && isset($this->creators[ $rel['id'] ])) {
				$pledge = Pledge::fromCreatorAndPledge($this->creators[ $rel['id'] ], $pledge);
				$pledges[$pledge->creator->creatorId] = $pledge;
			}
		}

		return $pledges;
	}

	public function getBills(int $year) : array {
		$data = $this->getJson('https://www.patreon.com/api/bills', [
			'timezone' => 'Europe/Berlin',
			'include' => 'campaign.creator.null',
			'fields' => [
				'user' => self::$fetchFields['user'],
				'campaign' => self::$fetchFields['campaign'],
				'bill' => self::$fetchFields['bill'],
				'patronage_purchase' => self::$fetchFields['patronage_purchase'] ?? self::$fetchFields['bill'],
			],
			'json-api-use-default-includes' => 'false',
			'filter' => [
				'due_date_year' => $year,
			],
			'json-api-version' => '1.0',
		]);

		$this->billableYears = $data['meta']['years'];

		$map = $this->mapIncluded($data['included'] ?? []);
		$this->persistCreatorsFromMappedCampaigns($map);

		$bills = [];
		foreach ($data['data'] as $bill) {
			$campaignId = $bill['relationships']['campaign']['data']['id'] ?? null;
			$creator = $campaignId ? $this->getCreatorFromCampaignId($campaignId) : null;
			$bill = Bill::fromCreatorAndBill($creator, $bill);
			$bills[$bill->id] = $bill;
		}

		return $bills;
	}

	protected function mapIncluded(array $included) : array {
		$map = []; // pledge, follow, campaign, user, bill, patronage_purchase
		foreach ($included as $object) {
			$map[ $object['type'] ][ $object['id'] ] = $object;
		}
		return $map;
	}

	protected function getCreatorFromCampaignId(string $id) : ?Creator {
		foreach ($this->creators as $creator) {
			if ($creator->campaignId == $id) {
				return $creator;
			}
		}

		return null;
	}

	protected function persistCreatorsFromMappedCampaigns(array $map) : void {
		$creators = array_filter(array_map(function(array $campaign) use ($map) {
			$userId = $campaign['relationships']['creator']['data']['id'] ?? null;
			if (!$userId) return null;
			$user = $map['user'][$userId];
			return Creator::fromUserAndCampaign($user, $campaign);
		}, $map['campaign']));
		$this->persistCreators($creators);
	}

	protected function persistCreatorsFromMappedUsers(array $map) : void {
		$creators = array_map(function(array $user) use ($map) {
			$campaignId = $user['relationships']['campaign']['data']['id'];
			$campaign = $map['campaign'][$campaignId];
			return Creator::fromUserAndCampaign($user, $campaign);
		}, $map['user']);
		$this->persistCreators($creators);
	}

	protected function persistCreators(array $creators) : void {
		foreach ($creators as $creator) {
			$this->persistCreator($creator);
		}
	}

	protected function persistCreator(Creator $creator) : void {
		if (!isset($this->creators[$creator->creatorId])) {
			$this->creators[$creator->creatorId] = $creator;
		}
	}

	public function logIn() : bool {
		return $this->auth->logIn($this) && $this->checkSession();
	}

	public function ensureCsrfToken() : string {
		if ($this->csrfToken) return $this->csrfToken;

		$rsp = $this->get('https://www.patreon.com/fdgfg5553fss' . rand() . '/creators');
		$html = (string) $rsp->getBody();

		if (!preg_match('#patreon\.csrfSignature\s*=\s*["\'](.+?)["\']#', $html, $match)) {
			throw new CantFindCsrfTokenException("patreon.csrfSignature");
		}

		return $this->csrfToken = $match[1];
	}

	protected function checkSession() : bool {
		$rsp = $this->get('https://www.patreon.com/api/current_user?fields[user]=email,patron_currency&json-api-version=1.0');

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

	protected function getJson(string $url, array $query) : array {
		$rsp = $this->get($url . '?' . http_build_query($query));
		$json = (string) $rsp->getBody();
		$data = json_decode($json, true);
		return $data;
	}

	protected function get(string $url) : Response {
// echo "$url\n";
		$this->_requests[] = "GET $url";
		return $this->guzzle->get($url);
	}

	protected function post(string $url, array $data) : Response {
		$csrfToken = $this->ensureCsrfToken();
// echo "$url\n";
		$this->_requests[] = "POST $url";
		return $this->guzzle->post($url, [
			'body' => json_encode($data),
			'headers' => [
				'Content-type' => 'application/vnd.api+json',
				'x-csrf-signature' => $csrfToken,
			],
		]);
	}

	protected function delete(string $url) : Response {
		$csrfToken = $this->ensureCsrfToken();
// echo "$url\n";
		$this->_requests[] = "DELETE $url";
		return $this->guzzle->delete($url, [
			'headers' => [
				'x-csrf-signature' => $csrfToken,
			],
		]);
	}

}
