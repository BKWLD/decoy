-# Header of table
.legend

	-# Stats
	%span.stat
		Total
		%span.badge=$count
	
	-# Potentially contain other buttons
	.pull-right.btn-toolbar

		-# Search togglers
		-if (!empty($search))
			.btn-group.search-controls.closed

				-# Search toggle
				%a.btn.btn-sm.outline.search-toggle
					.glyphicon.glyphicon-search

				-# Rest button
				%a.btn.btn-sm.outline.search-clear.js-tooltip(title="Reset")
					.glyphicon.glyphicon-ban-circle

-# Search UI
!=View::make('decoy::shared.list._search', $__data)


