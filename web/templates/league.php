<?php include("includes/header.php"); ?>
<h1>{{titolo}}</h1>
<h2>Classifica</h2>
<div>
	<table>
		<thead>
			<tr>
				<th>#</th>
				<th>Squadra</th>
				<th>G</th>
				<th>V</th>
				<th>N</th>
				<th>P</th>
				<th>GF</th>
				<th>GS</th>
				<th>Punti</th>
			</tr>
		</thead>
		<tbody>
			<?php $count = 1; foreach ($standings as $standing) { ?>
			<tr>
				<td><?php echo $count; ?></td>
				<td><?php echo $standing->team->teamname; ?></td>
				<td><?php echo $standing->played; ?></td>
				<td><?php echo $standing->won; ?></td>
				<td><?php echo $standing->draw; ?></td>
				<td><?php echo $standing->lost; ?></td>
				<td><?php echo $standing->goalscored; ?></td>
				<td><?php echo $standing->goalconceded; ?></td>
				<td><?php echo $standing->points; ?></td>
			</tr>
			<?php $count++; } ?>
		</tbody>
	</table>
</div>
<h2>Prossimo turno</h2>
<?php include("includes/footer.php"); ?>