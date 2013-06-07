<h1>Hello</h1>

<?// Login ?>
<div class="row">
	<div class="span6">	
		<?=Former::horizontal_open(action('Bkwld\Decoy\Controllers\Account@login'))?>
			<?=Former::token()?>

			<legend>Login</legend>
			
			<?= View::make('decoy::account._error') ?>
			
			<?=Former::text('email')->class('span3') ?>
			<?=Former::password('password')->class('span3') ?>
			<?= Former::checkbox('is_remember', false)->text('Remember me?')->check() ?>

			<div class="controls">
				<button type="submit" class="btn btn-primary">Login</button>
				<a href="<?=action('Bkwld\Decoy\Controllers\Account@forgot')?>" class="btn">Forgot Password</a>
			</div>
			
		<?=Former::close()?>
	</div>
	
	<?// Register ?>
	<div class="span5 offset1">	
		<?=Former::horizontal_open(action('Bkwld\Decoy\Controllers\Account@register'))?>
			<?=Former::token()?>

			<legend>Register</legend>
			
			<p><i class="icon-info-sign"></i> You must be granted access to this CMS by a current Admin.  You probably know who I'm talking about.</p>
			
		<?=Former::close()?>
	</div>
</div>