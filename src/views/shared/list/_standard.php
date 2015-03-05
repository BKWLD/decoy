<div class="standard-list fieldset" 
	data-js-view="standard-list" 
	data-controller-route="<?=URL::to(DecoyURL::action($controller))?>" 
	data-position-offset="<?=$paginator_from?>"
	<? if ($parent_controller):?> data-parent-controller="<?=$parent_controller?><?endif?>"
	>
	
	<?
	// Create the page title for the sidebar layout
	if ($layout == 'sidebar') echo View::make('decoy::shared.list._sidebar_header', $__data);
	
	// Create the page title for a full page layout
	else if ($layout == 'full') echo View::make('decoy::shared.list._full_header', $__data);

	// Render the full table.  This could be broken up into smaller chunks but leaving
	// it as is until the need arises
	echo View::make('decoy::shared.list._table', $__data);

	// Add sidebar pagination
	if (!empty($layout) && $layout != 'full' && $count > count($listing)): ?>
		<a href="<?=DecoyURL::relative('index', $parent_id, $controller)?>" class="btn btn-default btn-sm btn-block full-list">See full list of related <b><?=Str::title($title)?></b></a>
	<? endif ?>

</div>

<?
// Render pagination
echo View::make('decoy::shared.list._pagination', $__data);

?>