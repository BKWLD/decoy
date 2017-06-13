!= View::make('decoy::shared.form._header', $__data)->render()

%fieldset
	.legend= empty($item) ? 'New' : 'Edit'
	!= Former::text('title')
	!= Former::belongsTo('article_id', 'Article')->route('/admin/articles')->help('Just putting this here for integration tests. There is no good reason to do this.')

!= View::make('decoy::shared.form._footer', $__data)->render()
