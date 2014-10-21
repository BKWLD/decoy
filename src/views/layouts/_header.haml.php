.header

	-# Mobile controls
	-#
		%span.glyphicon.glyphicon-th-list
		%span.glyphicon.close.glyphicon-remove

	%h1.title

		-# The page title
		%span.site #{Config::get('decoy::site_name')} / 
		%span.controller!=$title

		-# Controller actions
		-if(app('decoy.auth')->can('create', $controller))
			.pull-right.btn-toolbar
				.btn-group
					%a.btn.outline.new(href=URL::to(DecoyURL::relative('create')))
						%span.glyphicon.glyphicon-plus 
						New

	-# Description of the controller
	-if($description)
		%h2.description!=$description