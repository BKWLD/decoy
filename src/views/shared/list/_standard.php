<?// This partial is populated through the help of a view composer as well as a special helper ?>

<div class="standard-list" 
	data-js-view="standard-list" 
	data-controller-route="<?=URL::to(HTML::controller($controller))?>" 
	<? if ($parent_controller):?> data-parent-controller="<?=URL::to(HTML::controller($parent_controller))?><?endif?>
	">
	
	<?
	// Create the page title for the sidebar layout
	if ($sidebar) echo View::make('decoy::shared.list._sidebar_header', $__data);
	
	// Create the page title for a full page layout
	else echo View::make('decoy::shared.list._full_header', $__data);

	// Render the full table.  This could be broken up into smaller chunks but leaving
	// it as is until the need arises
	echo View::make('decoy::shared.list._table', $__data);
	
	// Render pagination
	echo View::make('decoy::shared.list._pagination', $__data);
	?>

</div>