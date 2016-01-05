-$auth = App::make('decoy.auth')
.top
	%a.dashboard(href=$auth->getUserUrl())
		%img.gravatar(src=$auth->getUserPhoto())
		%span.name
			-if(($name = $auth->getShortName()) == "Default")
				Hi there!
			-else
				!='Hi, '.$name
	%a.btn.outline.close-nav
		%span.glyphicon.close.glyphicon-remove
