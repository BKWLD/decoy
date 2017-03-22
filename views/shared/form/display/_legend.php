<?php // Just the legend for the display_module?>

<div class="legend">
		Display
		<?php if (!empty($item) && ($url = $item->getUriAttribute())): ?>
			<a href="<?=$url?>" 
				target="_blank" 
				class="btn btn-default btn-sm outline pull-right">
				<span class="glyphicon glyphicon-bookmark"></span> View
			</a>
		<?php endif ?>
</div>