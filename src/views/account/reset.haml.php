!= View::make('decoy::shared.form._header', $__data)->render()

%fieldset
	.legend Reset
	%p Hey #{$user->first_name}.  Enter a new password and we'll log you in.
	
	!=Former::text('email')
	!=Former::password('password')
	!=Former::password('password_confirmation', 'Password, again')

	.form-actions
		%button(type="submit" class="btn btn-primary") Submit
		%a.btn.btn-default(href=route('decoy')) Cancel

!=Former::close()
	