!= View::make('decoy::account._error')
!= View::make('decoy::shared.form._header', $__data)

%fieldset
	.legend Reset
	%p Enter your email address and we'll email you a link to reset your password.  The email will come from #{Config::get('decoy::core.mail_from_address')}.
	
	!=Former::text('email')

	.form-actions
		%button(type="submit" class="btn btn-primary") Submit
		-$route = route('decoy\account@forgot')
		%a.btn.btn-default(href=$route) Cancel

!=Former::close()