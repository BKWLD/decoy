%fieldset
	.legend Element

	!=Former::vertical_open_for_files()
	!=Former::text('title', 'Marquee title')->blockHelp('Only make the title super sweet.');

	.form-actions
		%button.btn.btn-success.save(name="_save" value="save" type="submit")
			%span.glyphicon.glyphicon-file.glyphicon
			Save
		%span.btn.btn-default.back Close

	!=Former::close()

