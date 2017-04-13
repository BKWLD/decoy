<?php // Just the legend for the display_module?>

<div class="legend">
		<?php echo __('decoy::display.legend.title'); ?>
		<?php if (!empty($item) && ($url = $item->getUriAttribute())): ?>
			<a href="<?=$url?>"
				target="_blank"
				class="btn btn-default btn-sm outline pull-right">
				<span class="glyphicon glyphicon-bookmark"></span>
                <?php echo __('decoy::display.legend.view'); ?>
			</a>
		<?php endif ?>
</div>
