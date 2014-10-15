-#
	This partial is used to generate all of the HTML and layout that comes
	after most CRUD forms.  It is used in conjunction with _form_header.
	It expects:

		- controller : A string depicting the controller.  This is used in
			generating links.  I.e. 'admin.news'
			
		- item (optional) : The data that is being edited

		- actions (optional) : HTML for additional buttons

-use Bkwld\Decoy\Breadcrumbs

%hr

-# Push over for horizontal forms
.form-actions.col-lg-offset-2.col-lg-10.col-sm-offset-3.col-sm-9

	-# Save
	.btn-group
		-if (app('decoy.auth')->can('update', $controller))
			%button.btn.btn-success.save(name="_save" value="save" type="submit")
				%span.glyphicon.glyphicon-file.glyphicon
				Save
		-if (app('decoy.auth')->can('update', $controller) && app('decoy.auth')->can('create', $controller))
			%button.btn.btn-success.save_new(name="_save" value="new" type="submit") &amp; New
		-if (app('decoy.auth')->can('update', $controller))
			%button.btn.btn-success.save_back(name="_save" value="back" type="submit") &amp; Back

	-# Additional buttons
	-if (isset($actions)) echo $actions
		
	-# Delete
	-if (!empty($item) && app('decoy.auth')->can('destroy', $controller))
		%a.btn.btn-danger.delete(href=DecoyURL::relative('destroy', $item->id))
			%span.glyphicon.glyphicon-trash.glyphicon
			Delete
	
	-# Cancel
	%a.btn.btn-default.back(href=Breadcrumbs::smartBack()) Back

!=Former::close()
