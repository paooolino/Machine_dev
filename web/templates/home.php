<?php
/**
 *	@param $leagues Array
 */
?>
<?php include("includes/header.php"); ?>
<?php foreach ($leagues as $league) { ?>
	<div class="menu_item_big">
		<div class="picture">
			<img src="<?php echo ""; ?>" />
		</div>
		<div class="label">
			<?php echo $league->name; ?>
		</div>
	</div>
<?php } ?>
<?php include("includes/footer.php"); ?>