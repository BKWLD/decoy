<?// Show an alert box with data passed via the session. ?>
<? if($errors->any()): ?>
	<div class="alert alert-danger" role="alert">
		<button type="button" class="close" data-dismiss="alert">×</button>
		<?=join(' ', $errors->all())?>
	</div>
<? endif ?>

<? if (Session::has('login_notice')): ?>
	<div class="alert alert-warning" role="alert">
		<button type="button" class="close" data-dismiss="alert">×</button>
		<?= Session::get('login_notice') ?>
	</div>
<? endif ?>