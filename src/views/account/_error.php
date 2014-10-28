<?// Show an alert box with data passed via the session. ?>
<? if (Session::has('login_error')): ?>
	<div class="alert alert-danger" role="alert">
		<button type="button" class="close" data-dismiss="alert">×</button>
		<?= Session::get('login_error') ?>
	</div>
<? endif ?>

<? if (Session::has('login_notice')): ?>
	<div class="alert alert-warning" role="alert">
		<button type="button" class="close" data-dismiss="alert">×</button>
		<?= Session::get('login_notice') ?>
	</div>
<? endif ?>