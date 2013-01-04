<?// This partial is populated through the help of a view composer as well as a special helper ?>

<div 
	data-js-view="editable-list <?=$many_to_many?'many-to-many':null?>" 
	data-controller-route="<?=action($controller)?>"
	<?if (!empty($parent_id)):?>data-parent-id="<?=$parent_id?>"<?endif?>
>
	
	<?// Create the page title for the sidebar layout?>
	<? if ($sidebar): ?>
		<legend><a href="<?=route($controller.'@child', $parent_id)?>"><?=$title?></a> <span class="badge badge-inverse"><?=$count?></span> 
			
			<?// If we've declared this relationship a many to many one, show the autocomplete ?>
			<? if ($many_to_many): ?>
				<form class="many-to-many-form pull-right">
					<div class="input-append">
					  <input class="span2" type="text" placeholder="Search">
					  <button class="btn btn-small" disabled type="submit"><i class="icon-tag"></i> Add</button>
					</div>
				</form>
				
			<?// Else it's a regular one to many, so show a link to create a new item ?>
			<? else: ?>
				<a href="<?=route($controller.'@new', $parent_id)?>" class="btn btn-info btn-small pull-right"><i class="icon-plus icon-white"></i> New</a>
			<? endif ?>
		</legend>
		
	<?// Create the page title for a full page layout?>
	<? else: ?>
		<h1>
			<?=$title?> <span class="badge badge-inverse"><?=$count?></span>
			
			<?// If we've declared this relationship a many to many one, show the autocomplete ?>
			<? if ($many_to_many): ?>
				<form class="many-to-many-form pull-right">
					<div class="input-append">
					  <input class="span2" type="text" placeholder="Search">
					  <button class="btn btn-info disabled" disabled type="submit"><i class="icon-plus icon-white"></i> Add</button>
					</div>
				</form>
			
			<?// Else it's a regular one to many, so show a link to create a new item ?>
			<? else: ?>
				<a href="<?=route($controller.'@new', !empty($parent_id)?$parent_id:null)?>" class="btn btn-info pull-right" ><i class="icon-plus icon-white"></i> New</a>
			<? endif ?>
			
			<?// Show description ?>
			<? if (!empty($description)):?>
				<small><?=$description?></small>
			<? endif ?>
		</h1>
	<? endif?>

	<table class="table">
		<thead>
				<tr>
					<th class="select-all"><i class="icon-check"></i></th>
					
					<?// Loop through the columns array and create columns?>
					<? foreach(array_keys($columns) as $column): ?>
						<th class="<?=strtolower($column)?>"><?=$column?></th>
					<? endforeach ?>
					
					<th>Actions</th>
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
				<?=render('decoy::shared._list_actions')?>
			<? endif ?>
			
			<?
			// Loop through the listing data.  It's stored differently if pagination was used
			if (isset($listing->results)) $iterator = $listing->results;
			else $iterator = $listing;
			foreach ($iterator as $item):
			?>
				<tr 
					data-model-id="<?=$many_to_many?$item->pivot_id(): $item->id?>"
					<? if (isset($item->position)):?>data-position="<?=$item->position?>"<? endif ?>
				>
					<td><input type="checkbox" name="select-row"></td>
					
					<?// Loop through columns and add columns ?>
					<? $column_names = array_keys($columns) ?>
					<? foreach(array_values($columns) as $i => $column): ?>					
						<td class="<?=strtolower($column_names[$i])?>">
							
							<?// Add an automatic link on the first column?>
							<? if (($i===0 && $auto_link == 'first') || $auto_link == 'all'): ?>
								<a href="<?=route($controller.'@edit', $item->id)?>">
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
						<? if (!$many_to_many && isset($item->visible)): ?>
							<? if ($item->visible): ?>
								<a href="#" class="visibility"><i class="icon-eye-open js-tooltip" data-placement='left' title="Make hidden"></i></a>
							<? else: ?>
								<a href="#" class="visibility"><i class="icon- js-tooltip" data-placement='left' title="Make visible"></i></a>
							<? endif ?>
							|
						<? endif ?>
						
						<?// Edit link?>
						<a href="<?=route($controller.'@edit', $item->id)?>"><i class="icon-pencil" title="Edit"></i></a>
						| 
						 
						 <?// Many to many listings have remove icons instead of trash?>
						<? if ($many_to_many): ?>
							<a href="<?=route($controller.'@remove', $item->pivot_id())?>" class="remove-now"><i class="icon-remove js-tooltip" data-placement='left' title="Remove relationship"></i></a>
							
						<?// Regular listings actually delete rows ?>
						<? else: ?> 
							<a href="<?=route($controller.'@delete', $item->id)?>" class="delete-now"><i class="icon-trash js-tooltip" data-placement='left' title="Permanently delete"></i></a>
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
	
	<?// Show pagination ?>
	<? if ($sidebar): ?>
		<? if ($count > count($iterator)): ?>
			<a href="<?=route($controller.'@child', $parent_id)?>" class="btn btn-small btn-block full-list">See full list of related <?=strtolower($title)?></a>
		<? endif ?>
	<? elseif (method_exists($listing, 'links')): ?>
		<?=$listing->links(); ?>
	<? endif?>

</div>