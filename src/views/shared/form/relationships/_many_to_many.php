<?// This view regularly gets pulled into list._standard ?>

<form class="many-to-many-form" data-js-view="many-to-many<?=!empty($tags)?'-tags':null?>" data-controller-route="<?=DecoyURL::action($controller)?>" data-parent-id="<?=$parent_id?>" data-parent-controller="<?=$parent_controller?>">
	<div class="input-group">
		<input type="text" class="form-control input-sm" placeholder="Search<?=!empty($tags)?' or create':null?>">
		<div class="input-group-btn">
			<button class="btn <?=$layout=='sidebar'?'btn-sm':null?> outline" disabled type="submit">
				<? if (empty($tags)): ?><span class="glyphicon glyphicon-tag"></span> Add
				<? else: ?><span class="glyphicon glyphicon-plus"></span> New
				<?endif?>
			</button>
		</div>
	</div>
</form>