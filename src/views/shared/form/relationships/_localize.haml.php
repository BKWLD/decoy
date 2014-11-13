-if($item)
	%fieldset.form-vertical.localize
		.legend Localize

		-# Show the compare interface
		-$localizations = $localize->other()
		-if(!$localizations->isEmpty())
			.form-group.compare
				%label.control-label Compare
				.radio
					%label
						%input(type="radio" name="compare" checked)
						None
				-foreach($localizations as $model)
					.radio
						%label
							%input(type="radio" name="compare")
							%strong=Config::get('decoy::site.locales')[$model->locale]
							=' - '
							%a(href=DecoyURL::relative('edit', $model->getKey()))!=$model->title()
				%p.help-block Choose an existing localization for this <b>#{$title}</b> to compare against.  Rollover form element to view the content in the selected localization.

		-# Create a new localization.
		-$locales = $localize->localizableLocales()
		-if(count($locales))
			.form-group.create
				%label.control-label Create

				-# The select menu
				%select.form-control
					-foreach($locales as $locale => $label)
						%option(value=$locale) A #{$label} localization
						.check
				
				-# Additional options
				.checkbox
					%label
						%input(type="checkbox" checked)
						Include text
				.checkbox
					%label
						%input(type="checkbox" checked)
						Include images and files
				
				-# Help
				%p.help-block Creating a new localization of the current <b>#{$title}</b> will create a copy of <i>this</i> <b>#{$title}</b> using the selected locale.  The new <b>#{$title}</b> will be set to "Hidden".

				-# Submit
				%button.btn.btn-default(name='localize')
					%span.glyphicon.glyphicon-plus.glyphicon
					Create				

		-else
			-# Not possible to localize
			.form-group.create.disabled
				%label.control-label Create
				%p.help-block This <b>#{$title}</b> cannot be further localized because each locale already has a copy.  The links under "Compare" can be used to view them.

-else
	%fieldset.disabled
		.legend Localize
		%p Localized copies of this <b>#{$title}</b> cannot be created until it is saved.