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
			<a href="{{Link|Get|/league/<?php echo $this->urlify($league->name); ?>/}}"><?php echo $league->name; ?></a>
		</div>
	</div>
<?php } ?>
<?php include("includes/footer.php"); ?>