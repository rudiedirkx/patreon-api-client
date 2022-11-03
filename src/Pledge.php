<?php

namespace rdx\patreon;

class Pledge {

	public function __construct(
		public Creator $creator,
		public float $amount,
		public int $since,
	) {}

	static public function fromCreatorAndPledge(Creator $creator, array $pledge) : self {
		return new self(
			$creator,
			$pledge['attributes']['amount_cents'] / 100,
			strtotime($pledge['attributes']['created_at'])
		);
	}

}
