<?=View::make('decoy::shared.form._header', array(
	'title'       => $title,
	'controller'  => $controller,
	'item'        => @$item,
	'description' => @$description,
))?>
	
	
	<?= Former::text('email')->class('span9') ?>
	<?= Former::text('password')
		->class('span9')
		->forceValue(empty($item)?Str::random(16):null)
		->placeholder(empty($item)?null:'Leave blank to prevent change') ?>

	<div class="row">
		<div class="span6"><?= Former::text('first_name', 'First name')->class('span3') ?></div>
		<div class="span6"><?= Former::text('last_name', 'Last name')->class('span3') ?></div>
	</div>

	<?= Former::checkbox('send_email', false)
		->value(1)
		->text(empty($item)?
			'Send welcome email, including password':
			'Email '.$item->first_name.' with changes') ?>
		
	<hr/>
	<div class="controls">
		<button type="submit" class="btn btn-success"><i class="icon-file icon-white"></i> Save</button>
		
		<? if (!empty($item)): ?>
			
			<? if (!$item->disabled()): ?>
				<a class="btn btn-warning js-tooltip" href="<?=URL::to(HTML::relative('disable', $item->id))?>" title="Remove ability to login">
					<i class="icon-ban-circle icon-white"></i> Disable
				</a>
				
			<? else: ?>
				<a class="btn btn-warning js-tooltip" href="<?=URL::to(HTML::relative('enable', $item->id))?>" title="Allow admin to login again">
					<i class="icon-ok-circle icon-white"></i> Enable
				</a>
			<? endif ?>
			
			<a class="btn btn-danger" href="<?=URL::to(HTML::relative('destroy', $item->id))?>">
				<i class="icon-trash icon-white"></i> Delete
			</a>
		<? endif ?>
		
		<a class="btn" href="<?=URL::to(HTML::relative())?>">Cancel</a>
	</div>
		
<?= Former::close() ?>
