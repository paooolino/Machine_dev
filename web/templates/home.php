<?php
/**
 *	@param $leagues Array
 */
?>
<?php include("includes/header.php"); ?>
<div class="menurow">
	<?php foreach ($leagues as $league) { ?>
		<div class="menu_item_big dark">
			<a href="{{Link|Get|/league/<?php echo $league->slug; ?>/}}">
				<div class="picture">
					<img src="<?php echo ""; ?>" />
				</div>
				<div class="label">
					<?php echo $league->name; ?>
				</div>
			</a>
		</div>
	<?php } ?>
	<div class="close"></div>
</div>
<?php include("includes/footer.php"); ?>