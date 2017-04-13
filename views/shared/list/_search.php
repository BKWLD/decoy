<?php // The UI for the collapsable search menu for full listings?>
<?php
if (empty($search)) return;
$search = (new Bkwld\Decoy\Input\Search)->longhand($search);
?>

<form class="form-inline search" data-js-view="search" data-schema='<?=json_encode($search)?>' data-title='<?=strtolower($title)?>' >
	<div class="conditions">
		<?php // Most of the HTML is inserted by the backbone view ?>
		<button type="submit" class="btn btn-sm outline"><span class="glyphicon glyphicon-search"></span>
        <?php echo __('decoy::list.search'); ?></button>
	</div>
</form>
