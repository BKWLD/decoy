.fragments-wrap(data-js-view="fragments")

	-# Page title
	%h1.form-header 
		Fragments
		%small Special case text, images, and files.

	-# Show validation errors
	!=View::make('decoy::shared.form._errors')

	-# Form tag
	!=Former::open_vertical_for_files()->addClass('row')

	-# Create navigation
	.col-sm-3
		%ul.nav.nav-stacked.nav-pills.affixable.fragments-nav(role="tablist")
			-foreach(array_keys($fragments) as $i => $title)
				%li(class=$i===0?'active':null)
					%a(href='#'.Str::slug($title) data-slug=Str::slug($title) data-toggle="tab" role="tab")=$title

	-# Create pages
	.col-sm-9.tab-content
		-foreach($fragments as $title => $sections)
			.tab-pane(class=$title==current(array_keys($fragments))?'active':null id=Str::slug($title))
				
				-# Create sections
				-foreach($sections as $title => $pairs)
					%div
						%legend=$title
						
						-# Create pairs
						-foreach($pairs as $label => $value)
							:php
								switch($value->type) {
									case 'text': 
										echo Former::text($value->key, $label);
										break;
									case 'textarea': 
										echo Former::textarea($value->key, $label);
										break;
									case 'wysiwyg':
										echo Former::textarea($value->key, $label)->addClass('wysiwyg');
										break;
									case 'image':
										echo Former::image($value->key, $label);
										break;
									case 'video-encoder':
										echo Former::videoEncoder($value->key, $label);
										break;
									case 'file':
										echo Former::upload($value->key, $label);
										break;
									case 'belongs_to':
										echo Former::belongsTo($value->key, $label)->route($value->value);
										break;
								}
	
		%hr
		.controls.actions
			%button.btn.btn-success.save(type="submit")
				%span.glyphicon.glyphicon-file
				Save all tabs

	!=Former::close() 
