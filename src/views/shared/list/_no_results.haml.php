
%tr.no-results(class=$listing->count()?'remove':null)
	%td(colspan="999")
		%p
			
			-if($many_to_many)
				No <b><a href="#{URL::to(DecoyURL::relative('index', $parent_id, $controller))}" title="#{$description}" class="js-tooltip progress-link">#{$title}</a></b> 
				have been attached to this <b>#{str_singular($parent_controller_title)}</b>. 
				-if (app('decoy.auth')->can('update', $controller))
					<span class="nowrap">Use the <b><span class="glyphicon glyphicon-search"></span> Add</b></span> autocomplete field in this panel's header to find and attach one.
			
			-elseif ($parent_id)
				No <b><a href="#{URL::to(DecoyURL::relative('index', $parent_id, $controller))}" title="#{$description}" class="js-tooltip progress-link">#{$title}</a></b> 
				have been added to this <b>#{str_singular($parent_controller_title)}</b>. 
				-if (app('decoy.auth')->can('create', $controller))
					<span class="nowrap">Click <b><a href="#{URL::to(DecoyURL::relative('create', null, $controller))}"><span class="glyphicon glyphicon-plus"></span> New</a></b></span> to create one.
			
			-else
				No <b>#{$title}</b> have been created yet.
				-if (app('decoy.auth')->can('create', $controller))
					<span class="nowrap">Click <b><a href="#{URL::to(DecoyURL::relative('create', null, $controller))}"><span class="glyphicon glyphicon-plus"></span> New</a></b></span> to create one.

