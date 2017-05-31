- $sidebar->add(Former::listing('App\Slide'))

!= View::make('decoy::shared.form._header', $__data)->render()

%fieldset
	.legend= empty($item) ? 'New' : 'Edit'
	!= Former::text('title')
	!= Former::wysiwyg('body')
	!= Former::image()

%fieldset
	.legend Other
	!= Former::note('note', 'A lil note')
	!= Former::radiolist('category')->from(App\Article::$categories)->inline()
	!= Former::checklist('topic')->from(App\Article::$topics)
	!= Former::date('date')->value('now')
	!= Former::time('time')->value('now')
	!= Former::datetime('datetime')->value('now')->blockhelp('Enter a date and time')
	!= Former::manyToManyChecklist('tags')->addGroupClass('two-col')

%fieldset
	!= View::make('decoy::shared.form._display_module', $__data)->render()
	!= Former::checkbox('featured')->checkboxes(['Yes' => ['name' => 'featured', 'value' => 1]])->push()->blockHelp('Featured articles will show up in the ticker on the home page.')

!= View::make('decoy::shared.form._footer', $__data)->render()
