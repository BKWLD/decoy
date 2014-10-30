-# display errors or success messages after CRUD requests
-if ($errors->any())
	.notification-area.alert.alert-danger
		.notification-wrap
			.close
				%span.glyphicon.glyphicon-remove-circle
			%p
				%span.glyphicon.glyphicon-remove
				%strong Validation Error!
		  
				-if ($errors->has('slug'))
					A unique slug could not be formed from the name or title.  You must use a different value.

				-else
					The field in conflict is highlighted below.

-else if (Session::has('success'))
	.notification-area.alert.alert-success
		.notification-wrap
			.close
				%span.glyphicon.glyphicon-remove-circle
			%p
				%span.glyphicon.glyphicon-ok
				!=Session::get('success')
