<?php
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

// This is the deletable boolean if the listing is empty. If a many to many,
// set to true. If the user has permission to update their parent, then we'll
// want to include the bulk delete checkbox in `thead`.  If they don't have
// that permission, then they won't see the autocomplete to attach items anyway,
// so it doesn't matter that they see the bulk delete checkbox.
$can_delete = $many_to_many;

// Test the data for presence of special properties
if ($listing->count()) {

	// Get the list of actions
	$test_actions = $listing[0]->makeAdminActions($__data);

	// Check if the actions include a delete link
	$can_delete = count(array_filter($test_actions, function($action) {
			return strpos($action, 'delete-now') || strpos($action, 'remove-now');
		}))

			// ... and whether the user can delete this item
			&& (app('decoy.user')->can('destroy', $controller)

			// ... or, if many to many, update the parent
			|| ($many_to_many && app('decoy.user')->can('update', $parent_controller)));
}
?>

<table class="table listing columns-<?=count($columns)?>">
	<thead>
			<tr>

				<?php if ($can_delete): ?>
					<th class="select-all"><span class="glyphicon glyphicon-check"></span></th>
				<?php else: ?>
					<th class="hide"></th>
				<?php endif ?>

				<?php // Loop through the columns array and create columns?>
				<?php foreach(array_keys($columns) as $column): ?>
					<th class="<?=strtolower($column)?>"><?=$column?></th>
				<?php endforeach ?>

				<?php if (isset($test_actions)): ?>
					<?php if (count($test_actions)): ?>
						<th class="actions-<?=count($test_actions)?>"><?= __('decoy::list.table.actions') ?></th>
					<?php endif ?>
				<?php else: ?>
					<th class="actions-3"><?= __('decoy::list.table.actions') ?></th>
				<?php endif ?>

			</tr>
		</thead>
	<tbody>

		<?php // Many to many listings have a remove option, so the bulk actions change?>
		<?php if ($can_delete && $many_to_many): ?>
			<tr class="hide warning bulk-actions">
				<td colspan="999">
					<a class="btn btn-warning remove-confirm" href="#">
						<span class="glyphicon glyphicon-remove"></span>
						<?= __('decoy::list.table.remove_selected') ?>
					</a>
				</td>
			</tr>

		<?php // Standard bulk actions ?>
		<?php else: ?>
			<?=View::make('decoy::shared.list._bulk_actions')->render()?>
		<?php endif ?>

		<?php
		// Loop through the listing data
		foreach ($listing as $item): ?>

			<tr data-model-id="<?=$item->getKey()?>" class="<?=$item->getAdminRowClassAttribute()?>"
				<?php
				// Render parent id
				if (!empty($parent_id)) echo "data-parent-id='$parent_id' ";

				// Add position value from the row or from the pivot table.
				$position = isset($item->pivot)
					? $item->pivot->getAttribute('position')
					: $item->getAttribute('position');
				if (is_numeric($position)) echo "data-position='{$position}' ";
				?>
			>

				<?php // Checkboxes or bullets ?>
				<?php if ($can_delete): ?>
					<td><input
						type="checkbox"
						name="select-row"
						<?php if (method_exists($item, 'trashed')
							&& $item->trashed()) echo 'disabled'; ?>
						></td>
				<?php else: ?>
					<td class="hide"></td>
				<?php endif ?>

				<?php // Loop through columns and add columns ?>
				<?php $column_names = array_keys($columns) ?>
				<?php foreach(array_values($columns) as $i => $column): ?>
					<td class="<?=strtolower($column_names[$i])?>">

						<?php
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
				<?php endforeach ?>

				<?php // Standard action links?>
				<?php if (count($test_actions)): ?>
					<td class="actions">
						<?=implode(' | ', $item->makeAdminActions($__data))?>
					</td>
				<?php endif ?>

			</tr>
		<?php endforeach ?>

		<?php // Maybe there were no results found ?>
		<?=View::make('decoy::shared.list._no_results', $__data)->render()?>

	</tbody>
</table>
