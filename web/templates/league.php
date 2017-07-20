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
				<td><a href="{{Link|Get|/team/<?php echo $standing->team_id; ?>/}}"><?php echo $standing->team->teamname; ?></a></td>
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
<div>
	<table>
		<tbody>
			<?php foreach ($matches as $match) { ?>
			<tr>
				<td><?php echo $match->scheduledturn; ?></td>
				<td><?php echo $match->fetchAs('team')->team1->teamname; ?></td>
				<td><?php echo $match->fetchAs('team')->team2->teamname; ?></td>
				<?php if ($match->played) { ?>
					<td><?php echo $match->goal1; ?> - <?php echo $match->goal1; ?></td>
				<?php } else { ?>
					<td> - </td>
				<?php } ?>
			</tr>
			<?php } ?>
		</tbody>
	</table>
</div>
<?php include("includes/footer.php"); ?>