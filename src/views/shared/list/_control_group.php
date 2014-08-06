<div class="control-group list-control-group">
	<label class="control-label">
		<?=$title?>
		
		<? if (app('decoy.auth')->can('create', $controller)): ?>
			<div class="btn-group">
				<? $url = $many_to_many ? DecoyURL::action($controller.'@create') : DecoyURL::relative('create', null, $controller) ?>
				<a href="<?=URL::to($url)?>" class="btn btn-info btn-small new"><i class="icon-plus icon-white"></i> New</a>
			</div>
		<? endif ?>
		
	</label>
	<div class="controls">
		<div class="<?=empty($related)?'span9':'span6'?>">
			<?=View::make('decoy::shared.list._standard', $__data);?>
			<? if (!empty($description)):?>
				<p class="help-block"><?=$description?></p>
			<? endif ?>
		</div>
	</div>
</div>