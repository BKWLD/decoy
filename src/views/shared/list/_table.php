<?
/**
 * Render a table of model rows.  Required variables:
 * - listing
 * - columns
 * - controller
 */

// Set defaults for optional values so this partial can more easily be rendered
// by itself
if (!isset($many_to_many)) $many_to_many = false;
if (!isset($auto_link)) $auto_link = 'first';
if (!isset($convert_dates)) $convert_dates = 'date';

// Test the data for presence of special properties
$actions = 2; // Default
if ($listing->count()) {
	$test_row = $listing[0]->toArray();

	// Has visibilty toggle
	$has_visible = app('decoy.auth')->can('publish', $controller) && array_key_exists('visible', $test_row);
		
	// Increment the actions count
	if (!$many_to_many && $has_visible) $actions++;
}

?>

<table class="table listing columns-<?=count($columns)?>">
	<thead>
			<tr>

				<? if (app('decoy.auth')->can('destroy', $controller)): ?>
					<th class="select-all"><span class="glyphicon glyphicon-check"></span></th>
				<? else: ?>
					<th class="hide"></th>
				<? endif ?>

				<?// Loop through the columns array and create columns?>
				<? foreach(array_keys($columns) as $column): ?>
					<th class="<?=strtolower($column)?>"><?=$column?></th>
				<? endforeach ?>
				
				<th class="actions-<?=$actions?>">Actions</th>
			</tr>
		</thead>
	<tbody>
		
		<?// Many to many listings have a remove option, so the bulk actions change?>
		<? if ($many_to_many): ?>
			<tr class="hide warning bulk-actions">
				<td colspan="999">
					<a class="btn btn-warning remove-confirm" href="#">
						<span class="glyphicon glyphicon-remove"></span> Remove Selected
					</a>
				</td>
			</tr>
			
		<?// Standard bulk actions ?>
		<? else: ?>
			<?=View::make('decoy::shared.list._bulk_actions')?>
		<? endif ?>
		
		<?
		// Loop through the listing data
		foreach ($listing as $item):

			// Get the controller class from the model if it was not passed to the view.  This allows a listing to show
			// rows from multiple models
			if (empty($controller)) $controller = call_user_func(get_class($item).'::adminControllerClass');
		
			// Figure out the edit link
			if ($many_to_many) $edit = URL::to(DecoyURL::action($controller, $item->getKey()));
			else $edit = URL::to(DecoyURL::relative('edit', $item->getKey(), $controller));
			?>
	
			<tr data-model-id="<?=$item->getKey()?>"
				<?
				// Render parent id
				if (!empty($parent_id)) echo "data-parent-id='$parent_id' ";

				// Add position value from the row or from the pivot table.
				if (isset($test_row['pivot']) && array_key_exists('position', $test_row['pivot'])) echo "data-position='{$item->pivot->position}' ";
				else if (array_key_exists('position', $test_row)) echo "data-position='{$item->position}' ";
				?>
			>
				
				<?// Checkboxes or bullets ?>
				<? if (app('decoy.auth')->can('destroy', $controller)): ?>
					<td><input type="checkbox" name="select-row"></td>
				<? else: ?>
					<td class="hide"></td>
				<? endif ?>
				
				<?// Loop through columns and add columns ?>
				<? $column_names = array_keys($columns) ?>
				<? foreach(array_values($columns) as $i => $column): ?>					
					<td class="<?=strtolower($column_names[$i])?>">
						
						<?// Add an automatic link on the first column?>
						<? if (($i===0 && $auto_link == 'first') || $auto_link == 'all'): ?>
							<a href="<?=$edit?>">
						<? endif ?>	
						
						<?// Produce the value of the cell?>
						<?=Decoy::renderListColumn($item, $column, $convert_dates)?>	
						
						<?// End the automatic first link?>
						<? if (($i===0 && $auto_link == 'first') || $auto_link == 'all'): ?></a><?endif?>
					</td>
				<? endforeach ?>
				
				<?// Standard action links?>
				<td class="actions">
					
					<?// Toggle visibility link.  This requires JS to work. ?>
					<? if (!$many_to_many && $has_visible && app('decoy.auth')->can('update', $controller)): ?>
						<? if ($item->visible): ?>
							<a href="#" class="visibility js-tooltip" data-placement='left' title="Make draft"><span class="glyphicon glyphicon-eye-open"></span></a>
						<? else: ?>
							<a href="#" class="visibility js-tooltip" data-placement='left' title="Publish"><span class="glyphicon glyphicon-eye-close"></span></a>
						<? endif ?>
						<span class="visible-edit-seperator">|</span>
					<? endif ?>
					
					<?// Edit link?>
					<a href="<?=$edit?>"><span class="glyphicon glyphicon-pencil"></span></a>

					<?// Delete or remove ?>
					<? if (app('decoy.auth')->can('destroy', $controller)): ?>
						<span class="edit-delete-seperator">|</span>
						 
						 <?// Many to many listings have remove icons instead of trash?>
						<? if ($many_to_many): ?>
							<a href="#" class="remove-now js-tooltip" data-placement='left' title="Remove relationship"><span class="glyphicon glyphicon-remove"></span></a>
							
						<?// Regular listings actually delete rows ?>
						<? else: ?> 
							<a href="#" class="delete-now js-tooltip" data-placement='left' title="Permanently delete"><span class="glyphicon glyphicon-trash"></span></a>
						<? endif ?>
					<? endif ?>
				</td>
			</tr>
		<? endforeach ?>
		
		<?// Maybe there were no results found ?>
		<?=View::make('decoy::shared.list._no_results', $__data)?>
		
	</tbody>
</table>