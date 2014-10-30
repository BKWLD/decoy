<?// Just the legend for the display_module?>

<div class="legend">
		Display
		<? if (!empty($item) && ($url = $item->getUriAttribute())): ?>
			<a href="<?=$url?>" class="btn btn-default btn-sm outline pull-right"><span class="glyphicon glyphicon-bookmark"></span> View</a>
		<? endif ?>
</div>