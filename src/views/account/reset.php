<h1>Reset Password 
	<small>Almost done.</small>
</h1>

<?= View::make('decoy::account._error') ?>

<?=Former::horizontal_open()?>
	<?=Former::token()?>

	<legend>Reset</legend>
	<p>Hey <?=$user->first_name?>.  Enter a new password and we'll log you in.</p>
	
	<?=Former::password('password') ?>
	<?=Former::password('password_repeat', 'Password, again') ?>

	<hr/>
	<div class="controls col-lg-offset-2 col-lg-10 col-sm-offset-3 col-sm-9">
		<button type="submit" class="btn btn-primary">Submit</button>
		<a href="<?=route('decoy\account@forgot')?>" class="btn btn-default">Cancel</a>
	</div>
	
<?=Former::close()?>
