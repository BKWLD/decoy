.ui(data-js-view="login")
	.max-width
		.branding
			%h1=Config::get('decoy::site.name')
			%h4 Powered by <a href="http://bkwld.com">Decoy</a>
		
		!=Former::open_vertical(action('Bkwld\Decoy\Controllers\Account@login'))->addClass('form')
		
		-# Erorrs
		-if($errors->any())
			.alert.alert-danger
				%button( type="button" class="close" data-dismiss="alert") x
				=join(' ', $errors->all())

		-# Notices
		-if(Session::has('notice') || Session::has('login_notice'))
			.alert.alert-danger
				%button( type="button" class="close" data-dismiss="alert") x
				=Session::get('notice') ?: Session::get('login_notice')
		
		!= Former::text('email')->addGroupClass('form-inline')
		!= Former::password('password')->addGroupClass('form-inline')
		!= Former::checkbox('remember', ' ')->text('Remember me?')->check()->addGroupClass('form-inline fake-label')

		.form-group.form-inline.fake-label
			.buttons
				%button.btn.btn-primary(type="submit") Login
				%a.btn.btn-default(href=action('Bkwld\\Decoy\\Controllers\\Account@forgot')) Forgot Password
			
		!=Former::close()

.bkgd