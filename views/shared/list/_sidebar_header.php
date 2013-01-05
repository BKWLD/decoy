<?// The header above a list that is rendered into a sidebar ?>

<legend><a href="<?=route($controller.'@child', $parent_id)?>"><?=$title?></a> <span class="badge badge-inverse"><?=$count?></span> 
	
	<?// If we've declared this relationship a many to many one, show the autocomplete ?>
	<? if ($many_to_many): ?>
		<?=render('decoy::shared.form.autocomplete._many_to_many', $this->data())?>
		
	<?// Else it's a regular one to many, so show a link to create a new item ?>
	<? else: ?>
		<a href="<?=route($controller.'@new', $parent_id)?>" class="btn btn-info btn-small pull-right"><i class="icon-plus icon-white"></i> New</a>
	<? endif ?>
</legend>