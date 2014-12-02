-use Bkwld\Decoy\Breadcrumbs

-# Push over for horizontal forms
.form-actions

	-# Save
	.btn-group
		-if (app('decoy.auth')->can('update', $controller))
			%button.btn.btn-success.save(name="_save" value="save" type="submit")
				%span.glyphicon.glyphicon-file.glyphicon
				Save
		-if (app('decoy.auth')->can('update', $controller) && app('decoy.auth')->can('create', $controller))
			%button.btn.btn-success.js-tooltip.save_new(name="_save" value="new" type="submit" title="Go to create form after saving") &amp; New
		-if (app('decoy.auth')->can('update', $controller))
			%button.btn.btn-success.js-tooltip.save_back(name="_save" value="back" type="submit" title="Return to listing after saving") &amp; Back

	-# Additional buttons
	-if (isset($actions)) echo $actions
		
	-# Delete
	-if (!empty($item) && app('decoy.auth')->can('destroy', $controller))
		%a.btn.btn-danger.delete(href=DecoyURL::relative('destroy', $item->id))
			%span.glyphicon.glyphicon-trash.glyphicon
			Delete
	
	-# Cancel
	%a.btn.btn-default.back(href=Breadcrumbs::smartBack()) Cancel

// CLose the form
!=Former::close()

-# Close first column, show sidebar, and then close the row
-if(isset($sidebar) && !$sidebar->isEmpty())
	!='</div><div class="col-md-5 related">'
	!=$sidebar->render()
	!='</div></div>'
