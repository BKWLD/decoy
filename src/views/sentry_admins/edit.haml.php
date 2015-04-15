!= View::make('decoy::shared.form._header', $__data)

%fieldset
	.legend=empty($item)?'New':'Edit'
		
	!= Former::text('email')
	-if (Config::get('decoy::core.obscure_admin_password'))
		!= Former::password('password')
		!= Former::password('confirm_password')
	-else
		!= Former::text('password')->forceValue(empty($item)?Str::random(16):null)->placeholder(empty($item)?null:'Leave blank to prevent change')

	!= Former::text('first_name')
	!= Former::text('last_name')

	-if (($roles = Config::get('decoy::site.roles')) && !empty($roles))
		!= Former::radios('role')->radios(Bkwld\Library\Laravel\Former::radioArray($roles))

	!= Former::checkbox('send_email', ' ')->value(1)->text(empty($item)?'Send welcome email, including password':'Email '.$item->first_name.' with login changes')

	-# Create moderation actions
	-ob_start()
	-if (!empty($item) && app('decoy.auth')->can('update', $controller))

		-# Disable admin
		-if (!$item->disabled())
			%a.btn.btn-warning.js-tooltip(title="Remove ability to login" href=URL::to(DecoyURL::relative('disable', $item->id)))
				%span.glyphicon.glyphicon-ban-circle
				Disable
		-else
			%a.btn.btn-warning.js-tooltip(title="Restore ability to login" href=URL::to(DecoyURL::relative('enable', $item->id)))
				%span.glyphicon.glyphicon-ban-circle
				Enable
	-$actions = ob_get_clean();

!= View::make('decoy::shared.form._footer', array_merge($__data, ['actions' => $actions]))

