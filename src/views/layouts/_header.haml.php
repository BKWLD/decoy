.header

	-# Mobile controls
	.btn-group.nav-hamburger
		%a.btn.outline
			%span.glyphicon.glyphicon-th-list

	%h1.title

		-# The page title
		%span.site 
			#{Config::get('decoy::site.name')}
		%br.mobile-break
		%span.controller!=$title

		-if(!empty($many_to_many) && app('decoy.auth')->can('update', $controller))
			-# If we've declared this relationship a many to many one, show the autocomplete
			.pull-right.btn-toolbar
				!=View::make('decoy::shared.form.relationships._many_to_many', $__data)

		-else if(app('decoy.auth')->can('create', $controller) && !Route::is('decoy\elements', 'decoy\workers', 'decoy\commands', 'decoy\fragments'))
			-# Controller actions
			.pull-right.btn-toolbar
				.btn-group
					%a.btn.outline.new(href=URL::to(DecoyURL::relative('create')))
						%span.glyphicon.glyphicon-plus 
					!=View::make('decoy::shared.form._create-locales', ['title' => $title])

	-# Description of the controller
	-if($description)
		%h2.description!=$description
