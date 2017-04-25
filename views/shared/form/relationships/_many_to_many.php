<div class="many-to-many-form" data-js-view="many-to-many" data-controller-route="<?=DecoyURL::action($controller)?>" data-parent-id="<?=$parent_id?>" data-parent-controller="<?=$parent_controller?>">
	<input type="text" class="form-control <?=isset($layout)&&$layout=='sidebar'?'input-sm':null?>" placeholder="<?=__('decoy::list.many_to_many.add')?>">
	<span class="glyphicon glyphicon-search"></span>
</div>