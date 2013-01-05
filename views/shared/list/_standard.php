<?// This partial is populated through the help of a view composer as well as a special helper ?>

<div 
	data-js-view="standard-list <?=$many_to_many?'many-to-many':null?>" 
	data-controller-route="<?=action($controller)?>"
	<?if (!empty($parent_id)):?>data-parent-id="<?=$parent_id?>"<?endif?>
>
	
	<?
	// Create the page title for the sidebar layout
	if ($sidebar) echo render('decoy::shared.list._sidebar_header', $this->data());
	
	// Create the page title for a full page layout
	else echo render('decoy::shared.list._full_header', $this->data());

	// Render the full table.  This could be broken up into smaller chunks but leaving
	// it as is until the need arises
	echo render('decoy::shared.list._table', $this->data());
	
	// Render pagination
	echo render('decoy::shared.list._pagination', $this->data());
	?>

</div>