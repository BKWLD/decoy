-use Bkwld\Decoy\Models\Admin

-# Implement this as a collectoin instead, like fetch the collection of permission options
.form-group.admin-permisssions
	%label.control-label Permissions
	%div

		.checkbox
			%label
				%input(type='checkbox' name='_custom_permissions' value=1)
				Override default permissions for the selected Role

		.permissions-list
			-foreach(Admin::getPermissionOptions($item) as $controller)
				.controller
					%span.title.js-tooltip(title=$controller->description) = $controller->title

					.controller-permissions
						%input(type='hidden' name="_permission[#{$controller->slug}][]")
						-foreach($controller->permissions as $permission)
							%label.controller-permission
								%input(type='checkbox' name="_permission[#{$controller->slug}][]" value=$permission->slug checked=$permission->checked)
								%span.js-tooltip(title=$permission->description) = $permission->title