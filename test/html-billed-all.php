<?php

require 'inc.bootstrap.php';

$fnftPatreons = FNFT_PATREON_API_URL ? json_decode(file_get_contents(FNFT_PATREON_API_URL), true) : [];

$pledges = $client->getPledges();

$startYear = (int) date('Y');
$t = microtime(1);
$allBills = array_values($client->getBills($startYear));
$moreYears = array_diff($client->billableYears, [$startYear]);
foreach ($moreYears as $year) {
	$addBills = $client->getBills($year);
	$allBills = [...$allBills, ...array_values($addBills)];
}
$t = microtime(1) - $t;
usort($allBills, fn($a, $b) => $b->time <=> $a->time);

$activeTotals = [];
foreach ($allBills as $bill) {
	$activeTotals[$cid = $bill->creator->creatorId] ??= 0.0;
	$activeTotals[$cid] += $bill->amount;
}

?>
<title>All bills</title>
<meta name="viewport" content="width=device-width, initial-scale=1" />

<style>
table {
	border-collapse: collapse;
	border: solid 1px #ccc;
	border-width: 0 1px;
}
th, td {
	padding: 4px 6px;
	border: solid 1px #ccc;
	border-width: 1px 0;
}
.new-month > td {
	border-top: solid 2px #000;
}

a.active {
	color: green;
}
a.inactive {
	color: red;
}
</style>

<p>Loading all bills took <?= number_format($t * 1000, 0, '.', '_') ?> ms.</p>

<table>
	<thead>
		<tr>
			<th nowrap>Date</th>
			<th>Creator</th>
			<th></th>
			<th></th>
			<th>Amount</th>
			<th>Total</th>
		</tr>
	</thead>
	<tbody>
		<?
		$seenCids = [];
		$lastMonth = null;
		foreach ($allBills as $bill):
			$cid = $bill->creator->creatorId;
			$month = date('Y-m', $bill->time);
			$vanity = strtolower($bill->creator->vanity ?? sprintf('u:%s', $cid));
			$active = isset($pledges[$cid]);
			?>
			<tr
				data-creator="<?= $cid ?>"
				data-month="<?= date('Y-m', $bill->time) ?>"
				data-amount="<?= number_format($bill->amount, 2) ?>"
				class="<?= $lastMonth && $lastMonth != $month ? 'new-month' : '' ?>"
			>
				<td nowrap>
					<a href="#" data-filter-on="month"><?= $month ?></a><?= date('-d', $bill->time) ?>
				</td>
				<td>
					<a href="#" data-filter-on="creator" class="<?= $active ? 'active' : 'inactive' ?>">
						<?= html($bill->creator->campaignName) ?>
					</a>
				</td>
				<td><a href="<?= html($bill->creator->url) ?>" target="_blank">&#10132;</a></td>
				<td><?= html(implode(', ', $fnftPatreons[$vanity] ?? []) ?: $bill->creator->creation) ?></td>
				<td align="right"><?= $bill->currency ?> <?= number_format($bill->amount, 2) ?></td>
				<td align="right"><?= isset($seenCids[$cid]) ? '' : number_format($activeTotals[$cid] ?? 0, 2) ?></td>
			</tr>
			<?
			$seenCids[$cid] = true;
			$lastMonth = $month;
		endforeach ?>
	</tbody>
	<tfoot>
		<td colspan="4"></td>
		<td align="right"><strong id="total-amount"></strong></td>
		<td></td>
	</tfoot>
</table>

<script>
(function() {
	const tbody = document.querySelector('tbody');

	function printTotalAmount() {
		const total = [...tbody.rows].filter(tr => !tr.hidden).reduce((T, tr) => T + parseFloat(tr.dataset.amount), 0);
		document.querySelector('#total-amount').textContent = Math.round(total * 10) / 10;
	}

	function filterBy(field, value) {
		[...tbody.rows].forEach(tr => tr.hidden = value && tr.dataset[field] != value);

		printTotalAmount();
	}

	tbody.addEventListener('click', e => {
		if (e.target.matches('a[data-filter-on]')) {
			e.preventDefault();

			const filtering = tbody.dataset.filtering || '';
			const field = e.target.dataset.filterOn;
			if (filtering === field) {
				tbody.dataset.filtering = '';
				filterBy(field, null);
			}
			else {
				tbody.dataset.filtering = field;
				const value = e.target.closest(`[data-${field}]`).dataset[field];
				filterBy(field, value);
			}
		}
	});

	printTotalAmount();
})();
</script>

<hr>

<details>
	<summary>API requests (<?= count($client->_requests) ?>)</summary>
	<ul style="font-family: monospace; white-space: nowrap">
		<li><?= implode('</li><li>', $client->_requests) ?></li>
	</ul>
</details>
