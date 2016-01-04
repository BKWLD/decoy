-$auth = App::make('decoy.auth')
.top
	%a.dashboard(href=$auth->userUrl())
		%img.gravatar(src=$auth->userPhoto())
		%span.name
			-if($auth->userName() == "Default")
				Hi there!
			-else
				!='Hi, '.$auth->userName()
	%a.btn.outline.close-nav
		%span.glyphicon.close.glyphicon-remove