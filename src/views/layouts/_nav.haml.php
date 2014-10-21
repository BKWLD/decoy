-# This patial is populated from a view composer
-$auth = App::make('decoy.auth')
.sidebar
	
	.top
		%a(href=$auth->userUrl())
			.gravatar-wrap
				%img.gravatar(src=$auth->userPhoto())
			%span.name
				Hi
				-if($auth->userName() == "Default")
					there!
				-else
					,
					!=$auth->userName()
		.btn-group.close-nav
			%a.btn.outline
				%span.glyphicon.close.glyphicon-remove
	.nav
		.top-level-nav
			-foreach($pages as $page)
			
				-if (!empty($page->children))
					.main-nav(class=$page->active?'active':null)
						%a.top-level
							-if($page->icon)
								%span.glyphicon(class="glyphicon-#{$page->icon}")
							!=$page->label
	
						.subnav
							-foreach($page->children as $child)
								-if (!empty($child->divider))
								-elseif($auth->can('read', $child->url))
									%a(href=$child->url class=$child->active?'active':null)
										-if($child->icon)
											%span.glyphicon(class="glyphicon-#{$child->icon}")
										=$child->label
			
				-else if($auth->can('read', $page->url))
					%a(href=$page->url)=$page->label

			-if($auth->developer())
				.main-nav
					%a.top-level
						%span.glyphicon.glyphicon-cog
						Admin

					.subnav
						%a(href=DecoyURL::action('Bkwld\\Decoy\\Controllers\\Admins@index')) Admins
						%a(href=route('decoy\\commands')) Commands
						-if(count(Bkwld\Decoy\Models\Worker::all()))
							%a(href=route('decoy\\workers')) Workers

			-elseif(is_a($auth, 'Bkwld\Decoy\Auth\Sentry') && $auth->can('read', 'admins'))
				.main-nav
					%a.top-level(href=DecoyURL::action('Bkwld\\Decoy\\Controllers\\Admins@index')) 
						%span.glyphicon.glyphicon-user
						Admins
				