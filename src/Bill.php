<?php

namespace rdx\patreon;

class Bill {

	public function __construct(
		public string $id,
		public Creator $creator,
		public int $time,
		public float $amount,
		public string $currency,
	) {}

	static public function fromCreatorAndBill(Creator $creator, array $bill) : self {
		return new self(
			$bill['id'],
			$creator,
			strtotime($bill['attributes']['created_at']),
			$bill['attributes']['amount_cents'] / 100,
			$bill['attributes']['currency'],
		);
	}

}
