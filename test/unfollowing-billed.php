<?php

require 'inc.bootstrap.php';

$startYear = (int) date('Y');
$allBills = array_values($client->getBills($startYear));
// var_dump(count($allBills));
foreach (array_diff($client->billableYears, [$startYear]) as $year) {
	$addBills = $client->getBills($year);
	// var_dump(count($addBills));
	$allBills = [...$allBills, ...array_values($addBills)];
}
// var_dump(count($allBills));
echo "Total bills: " . count($allBills) . "\n";

$billCreatorIds = array_values(array_unique(array_map(fn($bill) => $bill->creator->id, $allBills)));
// print_r($billCreatorIds);
echo "Unique creators: " . count($billCreatorIds) . "\n";

$follows = $client->getFollows();
// print_r($follows);
echo "Following: " . count($follows) . "\n";

$followingCreatorIds = array_values(array_map(fn($follow) => $follow->creator->id, $follows));
$unfollowedCreatorIds = array_diff($billCreatorIds, $followingCreatorIds);
// print_r($unfollowedCreatorIds);
echo "Unfollowed: " . count($unfollowedCreatorIds) . "\n";
$unfollowedCreators = array_intersect_key($client->creators, array_flip($unfollowedCreatorIds));
print_r($unfollowedCreators);

echo "\n";
echo number_format(memory_get_peak_usage() / 1e6, 1) . " MB\n";
print_r($client->_requests);
