-# - $sidebar->add(Former::listing('Video'))
-# - $sidebar->add(Former::listing('Photo')->take(30))

!= View::make('decoy::shared.form._header', $__data)->render()

%fieldset
	.legend= empty($item) ? 'New' : 'Edit'
	!= Former::text('title')
	!= Former::wysiwyg('body')
	!= Former::image()

%fieldset
	.legend Other
	!= Former::radios('category')->radios(Bkwld\Library\Laravel\Former::radioArray(App\Article::$categories))->inline()
	!= Former::date('date')->value('now')

%fieldset
	!= View::make('decoy::shared.form._display_module', $__data)->render()
	!= Former::checkbox('featured')->checkboxes(['Yes' => ['name' => 'featured', 'value' => 1]])->push()->blockHelp('Featured articles will show up in the ticker on the home page.')

!= View::make('decoy::shared.form._footer', $__data)->render()
