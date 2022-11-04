<?php

namespace rdx\patreon;

class Follow {

	public function __construct(
		public string $id,
		public Creator $creator,
		public int $since,
	) {}

	static public function fromCreatorAndFollow(Creator $creator, array $follow) : self {
		return new self(
			$follow['id'],
			$creator,
			strtotime($follow['attributes']['created_at']),
		);
	}

}
