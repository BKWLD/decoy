<?=View::make('decoy::shared.form._header', array(
	'title'       => $title,
	'controller'  => $controller,
	'item'        => @$item,
	'description' => @$description,
))?>
	
	
	<?= Former::text('email')->class('span9') ?>
	<? if (Config::get('decoy::obscure_admin_password')): ?>
		<div class="row">
			<div class="span6"><?= Former::password('password') ->class('span3')?></div>
			<div class="span6"><?= Former::password('confirm_password') ->class('span3')?></div>
		</div>
	<? else: ?>
		<?= Former::text('password')
			->class('span9')
			->forceValue(empty($item)?Str::random(16):null)
			->placeholder(empty($item)?null:'Leave blank to prevent change') ?>
	<? endif ?>

	<div class="row">
		<div class="span6"><?= Former::text('first_name', 'First name')->class('span3') ?></div>
		<div class="span6"><?= Former::text('last_name', 'Last name')->class('span3') ?></div>
	</div>

	<? if(($roles = Config::get('decoy::roles')) && !empty($roles)): ?>
		<?= Former::radios('role')->radios(Bkwld\Library\Laravel\Former::radioArray($roles)) ?>
	<? endif ?>

	<?= Former::checkbox('send_email', false)
		->value(1)
		->text(empty($item)?
			'Send welcome email, including password':
			'Email '.$item->first_name.' with login changes') ?>
		
	<hr/>
	<div class="controls actions">
		<div class="btn-group">
			<? if (app('decoy.auth')->can('update', $controller)): ?>
				<button name="_save" value="save" type="submit" class="btn btn-success save"><i class="icon-file icon-white"></i> Save</button>
			<? endif ?>
			<? if (app('decoy.auth')->can('update', $controller) && app('decoy.auth')->can('create', $controller)): ?>
				<button name="_save" value="new" type="submit" class="btn btn-success save_new">&amp; New</button>
			<? endif ?>
			<? if (app('decoy.auth')->can('update', $controller)): ?>
				<button name="_save" value="back" type="submit" class="btn btn-success save_back">&amp; Back</button>
			<? endif ?>
		</div>
		
		<? if (!empty($item) && app('decoy.auth')->can('update', $controller)): ?>
			
			<? if (!$item->disabled()): ?>
				<a class="btn btn-warning js-tooltip" href="<?=URL::to(DecoyURL::relative('disable', $item->id))?>" title="Remove ability to login">
					<i class="icon-ban-circle icon-white"></i> Disable
				</a>
				
			<? else: ?>
				<a class="btn btn-warning js-tooltip" href="<?=URL::to(DecoyURL::relative('enable', $item->id))?>" title="Allow admin to login again">
					<i class="icon-ok-circle icon-white"></i> Enable
				</a>
			<? endif ?>
			
			<a class="btn btn-danger" href="<?=URL::to(DecoyURL::relative('destroy', $item->id))?>">
				<i class="icon-trash icon-white"></i> Delete
			</a>
		<? endif ?>
		
		<a class="btn back" href="<?=Bkwld\Decoy\Breadcrumbs::smartBack()?>">Back</a>
	</div>
		
<?= Former::close() ?>
