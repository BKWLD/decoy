<?// This partial is populated through the help of a view composer as well as a special helper ?>

<div data-js-view="standard-list" data-controller-route="<?=action($controller)?>">
	
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