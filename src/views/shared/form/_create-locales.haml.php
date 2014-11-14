-# Add a locales dropdown menu to a create button
-if (($locales = Config::get('decoy::site.locales')) && count($locales) > 1)
	%button.btn.outline.dropdown-toggle(data-toggle='dropdown' aria-expanded='false' class=empty($small)?null:'btn-sm')
		%span.caret
		%span.sr-only Toggle Dropdown
	%ul.dropdown-menu(role='menu')
		-foreach($locales as $slug => $label)
			%li
				%a(href=URL::to(DecoyURL::relative('create')).'?locale='.$slug) New #{$label} #{Str::singular($title)}