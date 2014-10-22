.ui(data-js-view="login")
	.max-width
		.branding
			%h1=Config::get('decoy::site.name')
			%h4 Powered by <a href="http://bkwld.com">Decoy</a>
		
		!=Former::open_vertical(action('Bkwld\Decoy\Controllers\Account@login'))->addClass('form')
		!= View::make('decoy::account._error')
		
		!= Former::text('email')->addGroupClass('form-inline')
		!= Former::password('password')->addGroupClass('form-inline')
		!= Former::checkbox('is_remember', ' ')->text('Remember me?')->check()->addGroupClass('form-inline fake-label')

		.form-group.form-inline.form-actions.fake-label
			.control-label
			.buttons
				%button.btn.btn-primary(type="submit") Login
				%a.btn.btn-default(href=action('Bkwld\\Decoy\\Controllers\\Account@forgot')) Forgot Password
			
		!=Former::close()

.bkgd