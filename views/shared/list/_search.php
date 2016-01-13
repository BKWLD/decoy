<?// The UI for the collapsable search menu for full listings?>
<?
if (empty($search)) return;
$search = (new Bkwld\Decoy\Input\Search)->longhand($search);
?>

<form class="form-inline search" data-js-view="search" data-schema='<?=json_encode($search)?>' data-title='<?=strtolower($title)?>' >
	<div class="conditions">
		<?// Most of the HTML is inserted by the backbone view ?>
		<button type="submit" class="btn btn-sm outline"><span class="glyphicon glyphicon-search"></span> Search</button>
	</div>
</form>
