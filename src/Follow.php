<?php

namespace rdx\patreon;

class Follow {

	public function __construct(
		public Creator $creator,
		public int $since,
	) {}

	static public function fromCreatorAndFollow(Creator $creator, array $follow) : self {
		return new self($creator, strtotime($follow['attributes']['created_at']));
	}

}
