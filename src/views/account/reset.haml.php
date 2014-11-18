!= View::make('decoy::account._error')
!= View::make('decoy::shared.form._header', $__data)

%fieldset
	.legend Reset
	%p Hey #{$user->first_name}.  Enter a new password and we'll log you in.
	
	!=Former::password('password')
	!=Former::password('password_repeat', 'Password, again')

	.form-actions
		%button(type="submit" class="btn btn-primary") Submit
		%a.btn.btn-default(href=route('decoy/account@forgot')) Cancel

!=Former::close()
	