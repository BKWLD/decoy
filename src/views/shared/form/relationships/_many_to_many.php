<form class="many-to-many-form" data-js-view="many-to-many" data-controller-route="<?=DecoyURL::action($controller)?>" data-parent-id="<?=$parent_id?>" data-parent-controller="<?=$parent_controller?>">
	<div class="input-group">
		<input type="text" class="form-control <?=isset($layout)&&$layout=='sidebar'?'input-sm':null?>" placeholder="Search">
		<div class="input-group-btn">
			<button class="btn <?=isset($layout)&&$layout=='sidebar'?'btn-sm':null?> outline" disabled type="submit">
				<span class="glyphicon glyphicon-tag"></span> Add
			</button>
		</div>
	</div>
</form>