<?// Just the legend for the display_module?>

<legend>
		Display
		<? if (!empty($item) && ($url = $item->deepLink())): ?>
			<a href="<?=$url?>" class="btn btn-small pull-right"><i class="icon-bookmark"></i> View</a>
		<? endif ?>
</legend>