<div class="standard-list <?=$layout!='form'?'fieldset':null?>"
	data-js-view="standard-list"
	data-controller-route="<?=URL::to(DecoyURL::action($controller))?>"
	data-position-offset="<?=$paginator_from?>"
	data-with-trashed="<?=$with_trashed?>"
	<?php if ($parent_controller):?> data-parent-controller="<?=$parent_controller?><?php endif?>"
  >

	<?php
	// Create the page title for the sidebar layout
	if ($layout == 'sidebar') {
		echo View::make('decoy::shared.list._sidebar_header', $__data)->render();

	// Create the page title for a full page layout
	} else if ($layout == 'full') {
		echo View::make('decoy::shared.list._full_header', $__data)->render();
	}

	// Render the full table.  This could be broken up into smaller chunks but
	// leaving it as is until the need arises
	echo '<div class="listing-wrapper">'
		.View::make('decoy::shared.list._table', $__data)->render()
		.'</div>';

	// Add sidebar pagination
	if (!empty($layout) && $layout != 'full' && $count > count($listing)): ?>
		<a href="<?=DecoyURL::relative('index', $parent_id, $controller)?>" class="btn btn-default btn-sm btn-block full-list"><?= __('decoy::list.standard.related', ['title' => title_case($title)]) ?></b></a>
	<?php endif ?>

</div>

<?php
// Render pagination
echo View::make('decoy::shared.pagination.index', $__data)->render();

?>
