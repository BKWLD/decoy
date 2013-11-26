<div class="ui" data-js-view="login">
	<div class="max-width">
		<div class="branding">	
			<h1><?=Config::get('decoy::site_name')?></h1>
			<h4>Powered by <a href="http://bkwld.com">Decoy</a></h4>
		</div>
		
		<div class="form">	
			<?=Former::horizontal_open(action('Bkwld\Decoy\Controllers\Account@login'))?>
				<?=Former::token()?>
							
				<?= View::make('decoy::account._error') ?>
				
				<?=Former::text('email') ?>
				<?=Former::password('password') ?>
				<?= Former::checkbox('is_remember', false)->text('Remember me?')->check() ?>

				<div class="controls">
					<button type="submit" class="btn btn-primary">Login</button>
					<a href="<?=action('Bkwld\Decoy\Controllers\Account@forgot')?>" class="btn">Forgot Password</a>
				</div>
				
			<?=Former::close()?>
		</div>
	</div>
</div>
<div class="bkgd" <?if($image = Config::get('decoy::login_bkgd')):?>style="background-image: url(<?=$image?>)"<?endif?>></div>