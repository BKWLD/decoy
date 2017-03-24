!= View::make('decoy::shared.form._header', $__data)->render()

%fieldset
	.legend= empty($item) ? 'New' : 'Edit'
	!= Former::text('title')
	!= Former::wysiwyg('directions')
	!= Former::image()
	!= Former::upload('file')

%fieldset
	!= View::make('decoy::shared.form._display_module', $__data)->render()

!= View::make('decoy::shared.form._footer', $__data)->render()
