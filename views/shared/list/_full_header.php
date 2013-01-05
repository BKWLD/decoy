<?// The title that is pulled into a full width list ?>

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