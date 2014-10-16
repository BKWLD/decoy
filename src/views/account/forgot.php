<h1>Forgot Password 
	<small>You know the drill.</small>
</h1>

<?= View::make('decoy::account._error') ?>

<?=Former::horizontal_open()?>
	<?=Former::token()?>

	<legend>Reset</legend>
	<p>Enter your email address and we'll email you a link to reset your password.  The email will come from <?=Config::get('decoy::mail_from_address')?>.</p>
	
	<?=Former::text('email') ?>

	<hr/>
	<div class="controls col-lg-offset-2 col-lg-10 col-sm-offset-3 col-sm-9">
		<button type="submit" class="btn btn-primary">Submit</button>
		<a href="<?=route('decoy')?>" class="btn btn-default">Cancel</a>
	</div>
	
<?=Former::close()?>
