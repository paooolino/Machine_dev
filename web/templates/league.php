<?php include("includes/header.php"); ?>
<h1>{{titolo}}</h1>
<h2>Classifica</h2>
<div>
	<?php echo App->getStandings($league); ?>
</div>
<?php include("includes/footer.php"); ?>