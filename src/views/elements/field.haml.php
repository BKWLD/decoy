%fieldset
	.legend Element

	-# Inform Former
	-Former::populate($element)

	-# Display form
	!=Former::vertical_open_for_files()
	!=Former::hidden('key')
	!=Former::text('value', $element->label)->blockHelp($element->help);

	.form-actions
		%button.btn.btn-success.save(name="_save" value="save" type="submit")
			%span.glyphicon.glyphicon-file.glyphicon
			Save
		%span.btn.btn-default.back Cancel

	!=Former::close()

