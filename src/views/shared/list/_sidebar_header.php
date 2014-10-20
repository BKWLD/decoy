<?// The header above a list that is rendered into a sidebar ?>

<div class="legend sidebar-header"><a href="<?=DecoyURL::relative('index', $parent_id, $controller)?>"><?=$title?></a> <span class="badge"><?=$count?></span> 
	
	<div class="btn-toolbar pull-right">
	
	<?// If we've declared this relationship a many to many one, show the autocomplete ?>
	<? if ($many_to_many && app('decoy.auth')->can('update', $controller)): ?>
		<?=View::make('decoy::shared.form.relationships._many_to_many', $__data)?>
		
	<?// Else it's a regular one to many, so show a link to create a new item ?>
	<? elseif (app('decoy.auth')->can('create', $controller)): ?>
		<div class="btn-group">
			<a href="<?=URL::to(DecoyURL::relative('create', null, $controller))?>" class="btn outline btn-sm new"><span class="glyphicon glyphicon-plus"></span> New</a>
		</div>
	<? endif ?>
	
	</div>
</div>