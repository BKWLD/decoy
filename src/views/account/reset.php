<h1>Reset Password 
	<small>Almost done.</small>
</h1>

<?= View::make('decoy::account._error') ?>

<?=Former::horizontal_open()?>
	<?=Former::token()?>

	<legend>Reset</legend>
	<p>Hey <?=$user->first_name?>.  Enter a new password and we'll log you in.</p>
	
	<?=Former::password('password')->class('span9') ?>
	<?=Former::password('password_repeat', 'Password, again')->class('span9') ?>

	<div class="controls">
		<button type="submit" class="btn btn-primary">Submit</button>
		<a href="<?=route('decoy\account@forgot')?>" class="btn">Cancel</a>
	</div>
	
<?=Former::close()?>
