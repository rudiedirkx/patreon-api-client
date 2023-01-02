<?php

require 'inc.bootstrap.php';

$fnftPatreons = FNFT_PATREON_API_URL ? json_decode(file_get_contents(FNFT_PATREON_API_URL), true) : [];

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
		</tr>
	</thead>
	<tbody>
		<? foreach ($allBills as $bill):
			$vanity = strtolower($bill->creator->vanity ?? sprintf('u:%s', $bill->creator->creatorId));
			?>
			<tr
				data-creator="<?= $bill->creator->creatorId ?>"
				data-month="<?= date('Y-m', $bill->time) ?>"
				data-amount="<?= number_format($bill->amount, 2) ?>"
			>
				<td nowrap><a href="#" data-filter-on="month"><?= date('Y-m', $bill->time) ?></a><?= date('-d', $bill->time) ?></td>
				<td><a href="#" data-filter-on="creator"><?= html($bill->creator->campaignName) ?></a></td>
				<td><a href="<?= html($bill->creator->url) ?>" target="_blank">&#10132;</a></td>
				<td><?= html(implode(', ', $fnftPatreons[$vanity] ?? []) ?: $bill->creator->creation) ?></td>
				<td align="right"><?= $bill->currency ?> <?= number_format($bill->amount, 2) ?></td>
			</tr>
		<? endforeach ?>
	</tbody>
	<tfoot>
		<td colspan="4"></td>
		<td align="right"><strong id="total-amount"></strong></td>
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
