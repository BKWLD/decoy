-# Dependencies
-use Illuminate\Support\Collection

-# Form tag
!=Former::open_vertical_for_files()->addClass('row')

-# Create navigation
.col.tab-sidebar
	%ul.nav.nav-stacked.nav-pills(role="tablist")
		-foreach($elements->groupBy('page_label')->keys() as $i => $title)
			-$slug = Str::slug($title)
			%li(class=$i===0?'active':null)
				%a(href='#'.$slug data-slug=$slug data-toggle="tab" role="tab")=$title


-# Create pages
.col.tab-content
	-$first = 1
	-foreach($elements->groupBy('page_label') as $page => $sections)
		-$sections = Collection::make($sections)->groupBy('section_label')
		.tab-pane(class=$first--?'active':null id=Str::slug($page))
			
			-# Create sections
			-foreach($sections as $section => $fields)
				%fieldset
					.legend=$section
					
					-# Create pairs
					-foreach($fields as $element)
						:php
							switch($element->type) {
								case 'text': 
									echo Former::text($element->key, $element->label);
									break;
								case 'textarea': 
									echo Former::textarea($element->key, $element->label);
									break;
								case 'wysiwyg':
									echo Former::textarea($element->key, $element->label)->addClass('wysiwyg');
									break;
								case 'image':
									echo Former::image($element->key, $element->label);
									break;
								case 'file':
									echo Former::upload($element->key, $element->label);
									break;

								/**
								 * Not ported yet from Frags:
								 */
								// case 'video-encoder':
								// 	echo Former::videoEncoder($element->key, $element->label);
								// 	breakl
								// case 'belongs_to':
								// 	echo Former::belongsTo($element->key, $element->label)->route($element->value);
								// 	break;
							}

.controls.form-actions
	%button.btn.btn-success.save(type="submit")
		%span.glyphicon.glyphicon-file
		Save all tabs

!=Former::close() 
