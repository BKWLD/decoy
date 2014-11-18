-# Dependencies
-use Bkwld\Decoy\Controllers\Elements
-use Illuminate\Support\Collection

-# Form tag
!=Former::open_vertical_for_files()->addClass('row')

-# Create navigation
.col.tab-sidebar
	%ul.nav.nav-stacked.nav-pills(role="tablist")
		-$first = 0
		-foreach($elements->groupBy('page_label') as $page => $sections)
			-$slug = Str::slug($page)
			%li(class=$first++==0?'active':null)
				%a.js-tooltip(href='#'.$slug data-slug=$locale.'/'.$slug data-toggle="tab" role="tab" title=$sections[0]->page_help data-placement="left")=$page

-# Create pages
.col.tab-content
	-$first = 0
	-foreach($elements->groupBy('page_label') as $page => $sections)
		-$sections = Collection::make($sections)->groupBy('section_label')
		.tab-pane(class=$first++==0?'active':null id=Str::slug($page))
			
			-# Create sections
			-foreach($sections as $section => $fields)
				%fieldset
					.legend
						%span.js-tooltip(title=$fields[0]->section_help)=$section

					-# Create pairs
					-foreach($fields as $el)
						!= Elements::renderField($el)

.controls.form-actions
	%button.btn.btn-success.save(type="submit")
		%span.glyphicon.glyphicon-file
		Save all tabs

!=Former::close() 
