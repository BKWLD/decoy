<h1>Forgot Password 
	<small>You know the drill.</small>
</h1>

<?= render('decoy::account._error') ?>

<?=Former::horizontal_open()?>
	<?=Former::token()?>

	<legend>Reset</legend>
	<p>Enter your email address and then define a new password.  An email will be sent to you with a "confirmation" link.  Clicking that link will activate the new password you defined here and you will be logged in.</p>
	
	<?=Former::text('email')->class('span9') ?>
	<?=Former::password('password')->class('span9') ?>
	<?=Former::password('password_repeat', "Repeat it")->class('span9') ?>

	<div class="controls">
		<button type="submit" class="btn btn-primary">Submit</button>
		<a href="<?=action('decoy::')?>" class="btn">Cancel</a>
	</div>
	
<?=Former::close()?>
