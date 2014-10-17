-# This patial is populated from a view composer
-$auth = App::make('decoy.auth')
.nav
	.box.box-admin
		%span ADMIN

	%ul
		-foreach($pages as $page)
		
			-if (!empty($page->children))
		
				-# Buffer the output so that it is only shown if children were added.  There
				-# could be none if they were hidden by permissions rules
				-ob_start()
				-$child_added = false
		
				-# The pulldown
				%li.dropdown(class=$page->active?'active':null)
					%a.dropdown-toggle(href='#' data-toggle='dropdown')
						!=$page->label
						%span.caret
		
					-# The options
					%ul.dropdown-menu(role="menu")
						-foreach($page->children as $child)
							-if (!empty($child->divider))
								%li.divider
							-elseif($auth->can('read', $child->url))
								-$child_added = true
								%li(class=$child->active?'active':null)
									%a(href=$child->url)=$child->label
		
				-# Only show the dropdown if a child was added
				-if ($child_added) 
					-ob_end_flush()
				-else 
					-ob_end_clean()
		
			-else if($auth->can('read', $page->url))
				%li(class=$page->active?'active':null)
					%a(href=$page->url)=$page->label

-#	-# Add AJAX progress indicator
-#	!= View::make('decoy::layouts._ajax_progress')-#