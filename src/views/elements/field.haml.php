%fieldset
	.legend
		%span.js-tooltip(title=$element->page_help data-placement="bottom")=$element->page_label
		%span.glyphicon.glyphicon-chevron-right
		%span.js-tooltip(title=$element->section_help data-placement="bottom")=$element->section_label

	-# Inform Former
	-Former::populate($element)

	-# Display form
	!=Former::vertical_open_for_files()
	!=Former::hidden('key')
	:php
		switch($element->type) {
			case 'text': 
				echo Former::text('value', $element->label); break;
			case 'textarea': 
				echo Former::textarea('value', $element->label); break;
			case 'wysiwyg':
				echo Former::textarea('value', $element->label)->addClass('wysiwyg'); break;
			case 'image':
				echo Former::image('value', $element->label); break;
			case 'file':
				echo Former::upload('value', $element->label); break;

			/**
			 * Not ported yet from Frags:
			 */
			// case 'video-encoder':
			// 	echo Former::videoEncoder('value', $element->label); break;
			// case 'belongs_to':
			// 	echo Former::belongsTo('value', $element->label)->route($value->value); break;
		}

	.form-actions
		%button.btn.btn-success.save(name="_save" value="save" type="submit")
			%span.glyphicon.glyphicon-file.glyphicon
			Save
		%span.btn.btn-default.back Cancel

	!=Former::close()

