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
if (!isset($convert_dates)) $convert_dates = 'date';
$can_delete = false;

// Test the data for presence of special properties
if ($listing->count()) {
	$test_row = $listing[0]->toArray();

	// Get the list of actions
	$test_actions = $listing[0]->makeAdminActions($__data);

	// Check if the actions include a delete link and whether the user can delete 
	// this item or, if many to many, update the parent.
	$can_delete = count(array_filter($test_actions, function($action) {
			return strpos($action, 'delete-now') || strpos($action, 'remove-now');
		})) && (app('decoy.auth')->can('destroy', $controller) 
			|| ($many_to_many && app('decoy.auth')->can('update', $parent_controller)));
}
?>

<table class="table listing columns-<?=count($columns)?>">
	<thead>
			<tr>

				<? if ($can_delete): ?>
					<th class="select-all"><span class="glyphicon glyphicon-check"></span></th>
				<? else: ?>
					<th class="hide"></th>
				<? endif ?>

				<?// Loop through the columns array and create columns?>
				<? foreach(array_keys($columns) as $column): ?>
					<th class="<?=strtolower($column)?>"><?=$column?></th>
				<? endforeach ?>
				
				<? if (isset($test_actions)): ?>
					<? if (count($test_actions)): ?>
						<th class="actions-<?=count($test_actions)?>">Actions</th>
					<? endif ?>
				<? else: ?>
					<th class="actions-3">Actions</th>
				<? endif ?>

			</tr>
		</thead>
	<tbody>
		
		<?// Many to many listings have a remove option, so the bulk actions change?>
		<? if ($can_delete && $many_to_many): ?>
			<tr class="hide warning bulk-actions">
				<td colspan="999">
					<a class="btn btn-warning remove-confirm" href="#">
						<span class="glyphicon glyphicon-remove"></span> Remove Selected
					</a>
				</td>
			</tr>
			
		<?// Standard bulk actions ?>
		<? else: ?>
			<?=View::make('decoy::shared.list._bulk_actions')->render()?>
		<? endif ?>
		
		<?
		// Loop through the listing data
		foreach ($listing as $item): ?>
	
			<tr data-model-id="<?=$item->getKey()?>" class="<?=$item->getAdminRowClassAttribute()?>"
				<?
				// Render parent id
				if (!empty($parent_id)) echo "data-parent-id='$parent_id' ";

				// Add position value from the row or from the pivot table.
				if (isset($test_row['pivot']) && array_key_exists('position', $test_row['pivot'])) echo "data-position='{$item->pivot->position}' ";
				else if (array_key_exists('position', $test_row)) echo "data-position='{$item->position}' ";
				?>
			>
				
				<?// Checkboxes or bullets ?>
				<? if ($can_delete): ?>
					<td><input type="checkbox" name="select-row"></td>
				<? else: ?>
					<td class="hide"></td>
				<? endif ?>
				
				<?// Loop through columns and add columns ?>
				<? $column_names = array_keys($columns) ?>
				<? foreach(array_values($columns) as $i => $column): ?>					
					<td class="<?=strtolower($column_names[$i])?>">
						
						<?
						// Wrap the column value in an edit link only if it's the first
						// column and it doesn't contain an a tag with an href attribute
						$value = Decoy::renderListColumn($item, $column, $convert_dates);
						if ($i ===0 && !preg_match('#<a[^.]+href[^.]+>#', $value)) {
							$value = '<a href="'
								.$item->getAdminEditUri($controller, $many_to_many)
								.'">'.$value.'</a>';
						} 
						echo $value; ?>

					</td>
				<? endforeach ?>
				
				<?// Standard action links?>
				<? if (count($test_actions)): ?>
					<td class="actions">
						<?=implode(' | ', $item->makeAdminActions($__data))?>
					</td>
				<? endif ?>

			</tr>
		<? endforeach ?>
		
		<?// Maybe there were no results found ?>
		<?=View::make('decoy::shared.list._no_results', $__data)->render()?>
		
	</tbody>
</table>