<?// The UI for the collapsable search menu for full listings?>
<? if (empty($search)) return ?>

<form class="form-inline search" data-js-view="search" data-schema='<?=json_encode($search)?>' data-title='<?=strtolower($title)?>' >
	<div class="conditions">
		<?// Most of the HTML is inserted by the backbone view ?>
		<button type="submit" class="btn"><i class="icon-search"></i> Search</button>
	</div>
</form>