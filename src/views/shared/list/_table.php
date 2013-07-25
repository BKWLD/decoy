<?
/**
 * Render a table of model rows.  Required variables:
 * - iterator
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
	$has_visible = array_key_exists('visible', $test_row);
		
	// Increment the actions count
	if (!$many_to_many && $has_visible) $actions++;
}

?>

<table class="table listing columns-<?=count($columns)?>">
	<thead>
			<tr>
				<th class="select-all"><i class="icon-check"></i></th>
				
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
						<i class="icon-remove icon-white"></i> Remove Selected
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
			?>
	
			<tr 
				data-model-id="<?=$many_to_many ? $item->pivot->id: $item->id?>"
				
				<?
				// Add position value from the row or from the pivot table.  
				if (array_key_exists('position', $test_row)) echo "data-position='{$item->position}'";
				elseif (isset($test_row['pivot']) && array_key_exists('position', $test_row['pivot'])) echo "data-position='{$item->pivot->position}'";
				
				// Figure out the edit link
				if ($many_to_many) $edit = URL::to(HTML::controller($controller, $item->id));
				else $edit = URL::to(HTML::relative('edit', $item->id, $controller));
				?>
			>
				<td><input type="checkbox" name="select-row"></td>
				
				<?// Loop through columns and add columns ?>
				<? $column_names = array_keys($columns) ?>
				<? foreach(array_values($columns) as $i => $column): ?>					
					<td class="<?=strtolower($column_names[$i])?>">
						
						<?// Add an automatic link on the first column?>
						<? if (($i===0 && $auto_link == 'first') || $auto_link == 'all'): ?>
							<a href="<?=$edit?>">
						<? endif ?>	
						
						<?// Produce the value of the cell?>
						<?=HTML::renderListColumn($item, $column, $convert_dates)?>	
						
						<?// End the automatic first link?>
						<? if (($i===0 && $auto_link == 'first') || $auto_link == 'all'): ?></a><?endif?>
					</td>
				<? endforeach ?>
				
				<?// Standard action links?>
				<td>
					
					<?// Toggle visibility link.  This requires JS to work. ?>
					<? if (!$many_to_many && $has_visible): ?>
						<? if ($item->visible): ?>
							<a href="#" class="visibility js-tooltip" data-placement='left' title="Make hidden"><i class="icon-eye-open"></i></a>
						<? else: ?>
							<a href="#" class="visibility js-tooltip" data-placement='left' title="Make visible"><i class="icon-"></i></a>
						<? endif ?>
						<span class="visible-edit-seperator">|</span>
					<? endif ?>
					
					<?// Edit link?>
					<a href="<?=$edit?>"><i class="icon-pencil" title="Edit"></i></a>
					<span class="edit-delete-seperator">|</span>
					 
					 <?// Many to many listings have remove icons instead of trash?>
					<? if ($many_to_many): ?>
						<a href="#" class="remove-now js-tooltip" data-placement='left' title="Remove relationship"><i class="icon-remove"></i></a>
						
					<?// Regular listings actually delete rows ?>
					<? else: ?> 
						<a href="#" class="delete-now js-tooltip" data-placement='left' title="Permanently delete"><i class="icon-trash"></i></a>
					<? endif ?>
				</td>
			</tr>
		<? endforeach ?>
		
		<?// Maybe there were no results found ?>
		<? if (!$listing->count()): ?>
			<tr>
				<td colspan="999">No results found</td>
			</tr>
		<? endif ?>
		
	</tbody>
</table>