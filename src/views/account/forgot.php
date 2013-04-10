<h1>Forgot Password 
	<small>You know the drill.</small>
</h1>

<?= View::make('decoy::account._error') ?>

<?=Former::horizontal_open()?>
	<?=Former::token()?>

	<legend>Reset</legend>
	<p>Enter your email address and we'll email you a link to reset your password.  The email will come from <?=Config::get('decoy::mail_from_address')?>.</p>
	
	<?=Former::text('email')->class('span9') ?>

	<div class="controls">
		<button type="submit" class="btn btn-primary">Submit</button>
		<a href="<?=action('decoy::')?>" class="btn">Cancel</a>
	</div>
	
<?=Former::close()?>
