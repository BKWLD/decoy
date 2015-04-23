-# This patial is populated from a view composer
-$auth = App::make('decoy.auth')
.sidebar
	
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
	.navigation
		.top-level-nav
			-foreach($pages as $page)
			
				-if (!empty($page->children))
					.main-nav(class=$page->active?'active open':null)
						%a.top-level.parent
							-if($page->icon)
								%span.glyphicon(class="glyphicon-#{$page->icon}")
							!=$page->label
	
						.subnav
							-foreach($page->children as $child)
								-if (!empty($child->divider))
								-elseif($auth->can('read', $child->url))
									%a(href=$child->url class=$child->active?'active':null)
										-if($child->icon != 'default')
											%span.glyphicon(class="glyphicon-#{$child->icon}")
										=$child->label
			
				-else if($auth->can('read', $page->url))
					.main-nav(class=$page->active?'active':null)
						%a.top-level(href=$page->url)
							-if($page->icon)
								%span.glyphicon(class="glyphicon-#{$page->icon}")
							!=$page->label

			-if($auth->developer())
				.main-nav(class=(in_array(Request::segment(2), ['admins', 'commands', 'workers']))?'active open':null)
					%a.top-level.parent
						%span.glyphicon.glyphicon-cog
						Admin

					.subnav
						%a(href=DecoyURL::action('Bkwld\\Decoy\\Controllers\\Admins@index') class=(Request::segment(2)=='admins'?'active':null)) Admins
						%a(href=route('decoy::commands') class=(Request::segment(2)=='commands'?'active':null)) Commands
						-if(count(Bkwld\Decoy\Models\Worker::all()))
							%a(href=route('decoy::workers')  class=(Request::segment(2)=='workers'?'active':null)) Workers

			-else
				.main-nav(class=Request::segment(2)=='admins'?'active':null)
					%a.top-level(href=DecoyURL::action('Bkwld\\Decoy\\Controllers\\Admins@index')) 
						%span.glyphicon.glyphicon-user
						Admins
				