<?// The header above a list that is rendered into a sidebar ?>

<legend class="sidebar-header"><a href="<?=$child_route?>"><?=$title?></a> <span class="badge badge-inverse"><?=$count?></span> 
	
	<div class="btn-toolbar pull-right">
	
	<?// If we've declared this relationship a many to many one, show the autocomplete ?>
	<? if ($many_to_many): ?>
		<?=render('decoy::shared.form.relationships._many_to_many', $this->data())?>
		
	<?// Else it's a regular one to many, so show a link to create a new item ?>
	<? else: ?>
		<div class="btn-group">
			<a href="<?=route($controller.'@new_child', $parent_id)?>" class="btn btn-info btn-small new"><i class="icon-plus icon-white"></i> New</a>
		</div>
	<? endif ?>
	
	</div>
</legend>