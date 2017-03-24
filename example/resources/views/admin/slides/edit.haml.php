!= View::make('decoy::shared.form._header', $__data)->render()

%fieldset
	.legend= empty($item) ? 'New' : 'Edit'
	!= Former::text('title')

!= View::make('decoy::shared.form._footer', $__data)->render()
