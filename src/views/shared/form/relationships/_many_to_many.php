<div class="many-to-many-form" data-js-view="many-to-many" data-controller-route="<?=DecoyURL::action($controller)?>" data-parent-id="<?=$parent_id?>" data-parent-controller="<?=$parent_controller?>">
	<div class="add-contributors">
		<span class="glyphicon glyphicon-search"></span>
		<input type="text" class="form-control <?=isset($layout)&&$layout=='sidebar'?'input-sm':null?>" placeholder="Add">
	</div>
</div>