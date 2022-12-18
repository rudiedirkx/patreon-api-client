<?php

require 'inc.bootstrap.php';

$startYear = (int) date('Y');
$t = microtime(1);
$allBills = array_values($client->getBills($startYear));
foreach (array_diff($client->billableYears, [$startYear]) as $year) {
	$addBills = $client->getBills($year);
	$allBills = [...$allBills, ...array_values($addBills)];
}
$t = microtime(1) - $t;
usort($allBills, fn($a, $b) => $b->time <=> $a->time);

?>
<p>Loading all bills took <?= number_format($t * 1000, 0, '.', '_') ?> ms.</p>

<table>
	<thead>
		<tr>
			<th>Date</th>
			<th></th>
			<th>Creator</th>
			<th></th>
			<th></th>
			<th>Amount</th>
		</tr>
	</thead>
	<tbody>
		<? foreach ($allBills as $bill): ?>
			<tr data-creator="<?= $bill->creator->creatorId ?>" data-amount="<?= number_format($bill->amount, 2) ?>">
				<td><?= date('Y-m-d', $bill->time) ?></td>
				<td><a href="#" class="filter-on-creator">&#128269;</a></td>
				<td><?= html($bill->creator->campaignName) ?></td>
				<td><a href="<?= html($bill->creator->url) ?>" target="_blank">&#10132;</a></td>
				<td><?= html($bill->creator->creation) ?></td>
				<td align="right"><?= $bill->currency ?> <?= number_format($bill->amount, 2) ?></td>
			</tr>
		<? endforeach ?>
	</tbody>
	<tfoot>
		<td colspan="5"></td>
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

	function filterByCreator(id) {
		[...tbody.rows].forEach(tr => tr.hidden = id && tr.dataset.creator != id);

		printTotalAmount();
	}

	tbody.addEventListener('click', e => {
		if (e.target.matches('a.filter-on-creator')) {
			e.preventDefault();

			const hiding = tbody.querySelector('tr[hidden]');
			if (hiding) {
				filterByCreator(null);
			}
			else {
				const creator = e.target.closest('[data-creator]').dataset.creator;
				filterByCreator(creator);
			}
		}
	});

	printTotalAmount();
})();
</script>
