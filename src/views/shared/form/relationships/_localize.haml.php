%fieldset.form-vertical.localize
	.legend Localize

	:php

		echo View::make('decoy::shared.form.display._locale', array_merge($__data, [
			'title' => 'New locale',
			'help' => "On submit, a new <b>{$model}</b> will be created with the current content but assigned to the selected locale. The visibility of the new <b>{$model}</b> will be \"Hidden\".",
		]));

		echo Former::checkboxes('Options')->checkboxes([
			'Include images and files' => ['name' => 'ass', 'value' => '', 'checked' => true],
		])->blockHelp("Customize what is copied into the new <b>{$model}</b>.");

	.form-group.actions
		%button.btn.btn-success
			%span.glyphicon.glyphicon-file.glyphicon
			Create
