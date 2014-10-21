.header

	-# Mobile controls
	.btn-group.nav-hamburger
		%a.btn.outline
			%span.glyphicon.glyphicon-th-list
	-# %span.glyphicon.close.glyphicon-remove

	%h1.title

		-# The page title
		%span.site 
			#{Config::get('decoy::site_name')}
		%br.mobile-break
		%span.controller!=$title

		-# Controller actions
		-if(app('decoy.auth')->can('create', $controller))
			.pull-right.btn-toolbar
				.btn-group
					%a.btn.outline.new(href=URL::to(DecoyURL::relative('create')))
						%span.glyphicon.glyphicon-plus 

	-# Description of the controller
	-if($description)
		%h2.description!=$description
