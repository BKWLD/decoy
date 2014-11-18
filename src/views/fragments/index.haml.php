-# Form tag
!=Former::open_vertical_for_files()->addClass('row')

-# Create navigation
.col.tab-sidebar
	%ul.nav.nav-stacked.nav-pills(role="tablist")
		-foreach(array_keys($fragments) as $i => $title)
			-$slug = Str::slug($title)
			-$active = (empty($tab) && $i === 0 ) || $slug == $tab
			%li(class=$active?'active':null)
				%a(href='#'.$slug data-slug=$slug data-toggle="tab" role="tab")=$title

-# Create pages
.col.tab-content
	-$first = 0
	-foreach($fragments as $title => $sections)
		-$slug = Str::slug($title)
		-$active = (empty($tab) && $first++ == 0 ) || $slug == $tab
		.tab-pane(class=$active?'active':null id=$slug)
			
			-# Create sections
			-foreach($sections as $title => $pairs)
				%fieldset
					.legend =$title
					
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

.controls.form-actions
	%button.btn.btn-success.save(type="submit")
		%span.glyphicon.glyphicon-file
		Save all tabs

!=Former::close() 
