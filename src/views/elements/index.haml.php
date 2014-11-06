-# Dependencies
-use Illuminate\Support\Collection

-# Form tag
!=Former::open_vertical_for_files()->addClass('row')

-# Create navigation
.col.tab-sidebar
	%ul.nav.nav-stacked.nav-pills(role="tablist")
		-$first = 1
		-foreach($elements->groupBy('page_label') as $page => $sections)
			-$slug = Str::slug($page)
			%li(class=$first--?'active':null)
				%a.js-tooltip(href='#'.$slug data-slug=$slug data-toggle="tab" role="tab" title=$sections[0]->page_help data-placement="left")=$page

-# Create pages
.col.tab-content
	-$first = 1
	-foreach($elements->groupBy('page_label') as $page => $sections)
		-$sections = Collection::make($sections)->groupBy('section_label')
		.tab-pane(class=$first--?'active':null id=Str::slug($page))
			
			-# Create sections
			-foreach($sections as $section => $fields)
				%fieldset
					.legend
						%span.js-tooltip(title=$fields[0]->section_help)=$section

					-# Create pairs
					-foreach($fields as $el)
						:php
							switch($el->type) {
								case 'text': 
									echo Former::text($el->key, $el->label)->blockHelp($el->help);
									break;
								case 'textarea': 
									echo Former::textarea($el->key, $el->label)->blockHelp($el->help);
									break;
								case 'wysiwyg':
									echo Former::textarea($el->key, $el->label)->addClass('wysiwyg')->blockHelp($el->help);
									break;
								case 'image':
									echo Former::image($el->key, $el->label)->blockHelp($el->help);
									break;
								case 'file':
									echo Former::upload($el->key, $el->label)->blockHelp($el->help);
									break;

								/**
								 * Not ported yet from Frags:
								 */
								// case 'video-encoder':
								// 	echo Former::videoEncoder($el->key, $el->label)->blockHelp($el->help);
								// 	breakl
								// case 'belongs_to':
								// 	echo Former::belongsTo($el->key, $el->label)->route($el->value)->blockHelp($el->help);
								// 	break;
							}

.controls.form-actions
	%button.btn.btn-success.save(type="submit")
		%span.glyphicon.glyphicon-file
		Save all tabs

!=Former::close() 
