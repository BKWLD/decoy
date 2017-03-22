<?php // The header above a list that is rendered into a sidebar ?>

<div class="legend sidebar-header"><a href="<?=URL::to(DecoyURL::relative('index', $parent_id, $controller))?>" title="<?=$description?>" class="js-tooltip progress-link"><?=$title?></a> <span class="badge"><?=$count?></span> 
	
	<div class="btn-toolbar pull-right">
	
	<?php // If we've declared this relationship a many to many one, show the autocomplete ?>

	<?php if ($many_to_many && app('decoy.user')->can('update', $parent_controller)): ?>
		<?=View::make('decoy::shared.form.relationships._many_to_many', $__data)->render()?>
		
	<?php // Else it's a regular one to many, so show a link to create a new item ?>
	<?php elseif (app('decoy.user')->can('create', $controller)): ?>
		<div class="btn-group">
			<a href="<?=URL::to(DecoyURL::relative('create', null, $controller))?>" class="btn outline btn-sm new progress-link"><span class="glyphicon glyphicon-plus"></span> New</a>
			<?=View::make('decoy::shared.form._create-locales', ['title' => $title, 'small' => true])->render() ?>
		</div>
	<?php endif ?>
	
	</div>
</div>