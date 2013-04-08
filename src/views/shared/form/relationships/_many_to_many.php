<?// This view regularly gets pulled into list._standard ?>

<form class="many-to-many-form" data-js-view="many-to-many<?=!empty($tags)?'-tags':null?>" data-controller-route="<?=action($controller)?>" data-parent-id="<?=$parent_id?>" data-parent-controller="<?=$parent_controller?>">
	<div class="input-append">
	  <input class="span2" type="text" placeholder="Search<?=!empty($tags)?' or create':null?>">
	  <button class="btn <?=$sidebar?'btn-small':null?>" disabled type="submit">
	  	<? if (empty($tags)): ?><i class="icon-tag"></i> Add
		  <? else: ?><i class="icon-plus"></i> New
		  <?endif?>
	 	</button>
	</div>
</form>