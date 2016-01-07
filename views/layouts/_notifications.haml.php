-# Building out the message strings, if any
:php
	$alert_type = '';
	$message = ' ';

	// Determine the alert type and build any related copy

	// ERROR
	if($errors->any()) {
		$alert_type = 'danger';

		if($errors->has('slug'))
			$message = 'A unique slug could not be formed from the name or title.  You must use a different value.';
		else if($errors->has('error message'))
			$message = $errors->first('error message');
		else
			$message = '<b>Validation error:</b> The field in conflict is highlighted below. Your submission was <b>not</b> saved.';

	// SUCCESS
	// "status" is used by Laravel's auth flow, which we piggyback on.
	} else if(Session::has('success') || Session::has('status')) {
		$alert_type = 'success';
		$message = Session::get('success', Session::get('status'));

	// NEUTRAL
	} else $alert_type = 'normal';

-# Display notifications after CRUD requests, AJAX errors, etc
-# If there's no message, data-display attribute will be false. Used in the JS to open up the pane.
.notification-area.alert(data-js-view="notification" data-alert-type=$alert_type data-display=($message!=' ')?'true':'false')
	.notification-wrap
		.close
			%span.glyphicon.glyphicon-remove-circle
		%p
			%span.glyphicon
			%span.message!=$message
