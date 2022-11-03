<?php

namespace rdx\patreon;

class Pledge {

	public function __construct(
		public Creator $creator,
		public float $amount,
	) {}

	static public function fromCreatorAndPledge(Creator $creator, array $pledge) {
		return new self($creator, $pledge['attributes']['amount_cents'] / 100);
	}

}
