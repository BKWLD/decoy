- $sidebar->add(Former::listing('App\Article'))

!= View::make('decoy::shared.form._header', $__data)->render()

%fieldset
	.legend= empty($item) ? 'New' : 'Edit'
	!= Former::text('name')

!= View::make('decoy::shared.form._footer', $__data)->render()
