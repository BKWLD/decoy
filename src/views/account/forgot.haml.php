!= View::make('decoy::shared.form._header', $__data)

%fieldset
	.legend Reset
	%p Enter your email address and we'll email you a link to reset your password.  The email will come from #{Config::get('decoy::core.mail_from_address')}.
	
	!=Former::text('email')

	.form-actions
		%button(type="submit" class="btn btn-primary") Submit
		%a.btn.btn-default(href=route('decoy')) Cancel

!=Former::close()