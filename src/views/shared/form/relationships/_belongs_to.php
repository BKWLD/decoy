<?// This view is rendered by the Decoy::belongsTo() macro ?>

<div class="belongs-to" data-js-view="belongs-to" data-controller-route="<?=$route?>">
	
	<?// Displayed to users
	if (Former::getValue($id)) $append = '<button type="button" class="btn btn-info"><i class="icon-pencil icon-white"></i></button>';
	else $append = '<button type="button" class="btn" disabled><i class="icon-ban-circle"></i></button>';
	$input = Former::text($id, $label)
		->class('span5 autocomplete')
		->placeholder('Search')
		->autocomplete('off')
		->append($append);
	if (!empty($title)) $input->forceValue($title);
	if (!empty($blockHelp)) $input->blockHelp($blockHelp);
	echo $input;
	?>
		
	<?// Submitted with POST ?>
	<?=Former::hidden($id)?>
</div>