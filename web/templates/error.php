<?php include("includes/header.php"); ?>
<h1>Attenzione: si è verificato un errore.</h1>
<ul>
<?php foreach ($errorMessages as $errorMessage) { ?>
	<li><?php echo $errorMessage; ?></li>
<?php } ?>
</ul>
<p><a href="javascript:history.back();">Torna indietro</a></p>
<?php include("includes/footer.php"); ?>