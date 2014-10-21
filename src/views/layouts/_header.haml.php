.header

	-# Mobile controls
	-#
		%span.glyphicon.glyphicon-th-list
		%span.glyphicon.close.glyphicon-remove

	%h1.title

		-# The page title
		%span.site 
			#{Config::get('decoy::site_name')} / 
			%br.mobile 
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

	-# The account menu
	-# Disabled for now, will be migrated into the nav
		-$auth = App::make('decoy.auth')
		%ul.user
			%li.dropdown
				
				-# Dropdown menu
				%a.dropdown-toggle(data-toggle='dropdown')
					.gravatar-wrap
						%img.gravatar(src=$auth->userPhoto())
					%span.caret
		
				-# Options
				%ul.dropdown-menu
		
					-if(is_a($auth, 'Bkwld\Decoy\Auth\Sentry') && $auth->can('read', 'admins'))
						%li
							%a(href=DecoyURL::action('Bkwld\\Decoy\\Controllers\\Admins@index')) Admins
						%li
							%a(href=$auth->userUrl()) Your account
						%li.divider
		
					-$divider = false
					-if($auth->developer())
						-$divider = true
						%li
							%a(href=route('decoy\\commands')) Commands
		
					-if(count(Bkwld\Decoy\Models\Worker::all()))
						-$divider = true
						%li
							%a(href=route('decoy\\workers')) Workers
		
					-if($divider)
						%li.divider
		
					%li
						%a(href='/') Public site
					%li
						%a(href=$auth->logoutUrl()) Log out