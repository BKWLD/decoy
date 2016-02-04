!= View::make('decoy::shared.form._header', $__data)->render()

%fieldset
	.legend= empty($item) ? 'New' : 'Edit'

	-# There is so much logic here, breaking out to php for more line breaking
	:php

		// Name
		echo Former::text('first_name');
		echo Former::text('last_name');

		// Email and password
		echo Former::text('email');
		if (Config::get('decoy.core.obscure_admin_password')) {
			echo Former::password('password');
			echo Former::password('confirm_password');
		} else {
			echo Former::text('password')
				->forceValue(empty($item) ? str_random(16) : null)
				->placeholder(empty($item) ? null : 'Leave blank to prevent change');
		}

		// Image
		echo Former::image('image');

		// Roles and permissions
		if (app('decoy.user')->can('grant', $controller)
			&& ($roles = Config::get('decoy.site.roles'))
			&& !empty($roles)) {
			echo Former::radios('role')
				->radios(Bkwld\Library\Laravel\Former::radioArray($roles));
			echo View::make('decoy::admins._permissions', $__data)->render();
		}

		// Send email
		echo Former::checkbox('_send_email', 'Notify')
			->value(1)
			->text(empty($item) ?
				'Send welcome email, including password' :
				'Email '.$item->first_name.' with login changes' );

	-# Create moderation actions
	-ob_start()
	-if (!empty($item) && app('decoy.user')->can('grant', $controller))

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

!= View::make('decoy::shared.form._footer', array_merge($__data, ['actions' => $actions]))->render()
