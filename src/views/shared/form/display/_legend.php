<?// Just the legend for the display_module?>

<div class="legend">
		Display
		<? if (!empty($item) && ($url = $item->deepLink())): ?>
			<a href="<?=$url?>" class="btn btn-default btn-xs pull-right"><span class="glyphicon glyphicon-bookmark"></span> View</a>
		<? endif ?>
</div>