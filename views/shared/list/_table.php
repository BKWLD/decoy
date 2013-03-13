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
if (count($iterator)) {
	$test_row = $iterator[0]->to_array();
	
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
			<?=render('decoy::shared.list._bulk_actions')?>
		<? endif ?>
		
		<?
		// Loop through the listing data
		foreach ($iterator as $item):

			// Base the controller name from the model name if it's not defined.  This allows a listing to show
			// rows from multiple models
			$controller_path = empty($controller) ? call_user_func(get_class($item).'::admin_controller') : $controller;
		?>
	
			<tr 
				data-model-id="<?=$many_to_many?$item->pivot_id(): $item->id?>"
				
				<?
				// Add position value from the row or from the pivot table.  
				if (array_key_exists('position', $test_row)) echo "data-position='{$item->position}'";
				elseif (isset($test_row['pivot']['position'])) echo "data-position='{$item->pivot->position}'";
				?>
			>
				<td><input type="checkbox" name="select-row"></td>
				
				<?// Loop through columns and add columns ?>
				<? $column_names = array_keys($columns) ?>
				<? foreach(array_values($columns) as $i => $column): ?>					
					<td class="<?=strtolower($column_names[$i])?>">
						
						<?// Add an automatic link on the first column?>
						<? if (($i===0 && $auto_link == 'first') || $auto_link == 'all'): ?>
							<a href="<?=HTML::edit_route($controller_path, $many_to_many, $item->id)?>">
						<? endif ?>	
						
						<?// Produce the value of the cell?>
						<?=HTML::render_list_column($item, $column, $convert_dates)?>	
						
						<?// End the automatic first link?>
						<? if (($i===0 && $auto_link == 'first') || $auto_link == 'all'): ?></a><?endif?>
					</td>
				<? endforeach ?>
				
				<?// Standard action links?>
				<td>
					
					<?// Toggle visibility link.  This requires JS to work. ?>
					<? if (!$many_to_many && $has_visible): ?>
						<? if ($item->visible): ?>
							<a href="#" class="visibility"><i class="icon-eye-open js-tooltip" data-placement='left' title="Make hidden"></i></a>
						<? else: ?>
							<a href="#" class="visibility"><i class="icon- js-tooltip" data-placement='left' title="Make visible"></i></a>
						<? endif ?>
						|
					<? endif ?>
					
					<?// Edit link?>
					<a href="<?=HTML::edit_route($controller_path, $many_to_many, $item->id)?>"><i class="icon-pencil" title="Edit"></i></a>
					| 
					 
					 <?// Many to many listings have remove icons instead of trash?>
					<? if ($many_to_many): ?>
						<a href="<?=route($controller_path.'@remove', $item->pivot_id())?>" class="remove-now"><i class="icon-remove js-tooltip" data-placement='left' title="Remove relationship"></i></a>
						
					<?// Regular listings actually delete rows ?>
					<? else: ?> 
						<a href="<?=route($controller_path.'@delete', $item->id)?>" class="delete-now"><i class="icon-trash js-tooltip" data-placement='left' title="Permanently delete"></i></a>
					<? endif ?>
				</td>
			</tr>
		<? endforeach ?>
		
		<?// Maybe there were no results found ?>
		<? if (empty($iterator)): ?>
			<tr>
				<td colspan="999">No results found</td>
			</tr>
		<? endif ?>
		
	</tbody>
</table>