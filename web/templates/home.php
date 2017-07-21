<?php
/**
 *	@param $leagues Array
 */
?>
<?php include("includes/header.php"); ?>
<div class="menurow">
	<?php foreach ($leagues as $league) { ?>
		<div class="menu_item_big">
			<div class="picture">
				<img src="<?php echo ""; ?>" />
			</div>
			<div class="label">
				<a href="{{Link|Get|/league/<?php echo $league->slug; ?>/}}"><?php echo $league->name; ?></a>
			</div>
		</div>
	<?php } ?>
</div>
<?php include("includes/footer.php"); ?>