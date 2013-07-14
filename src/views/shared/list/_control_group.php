<? // This partial loads the standard list into a control group container.

// Automatically update the data with the right layout
$__data['layout'] = 'control group';
?>

<div class="control-group list-control-group">
	<label class="control-label">
		<?=$title?>
		
		<?// So far, not supporting many-to-many in the control group layout?>
		<div class="btn-group">
			<a href="<?=URL::to(HTML::relative('create', null, $controller))?>" class="btn btn-info btn-small new"><i class="icon-plus icon-white"></i> New</a>
		</div>
		
	</label>
	<div class="controls">
		<?=View::make('decoy::shared.list._standard', $__data);?>
		<? if (!empty($description)):?>
			<p class="help-block"><?=$description?></p>
		<? endif ?>
	</div>
</div>