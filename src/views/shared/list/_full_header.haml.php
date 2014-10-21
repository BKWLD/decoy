
-# Disabling while porting

	<?// If we've declared this relationship a many to many one, show the autocomplete ?>
	<? if (!empty($many_to_many) && app('decoy.auth')->can('update', $controller)): ?>
		<?=View::make('decoy::shared.form.relationships._many_to_many', $__data)?>


-# Header of table
.legend

	-# Stats
	%span.stat
		Total
		%span.badge=$count
	%span.stat
		Showing
		%span.badge=$count
	
	-# Potentially contain other buttons
	.pull-right.btn-toolbar

		-# Search togglers
		-if (!empty($search))
			.btn-group.search-controls.closed

				-# Search toggle
				%a.btn.btn-sm.outline.search-toggle
					.glyphicon.glyphicon-search

				-# Rest button, change the default container to fix a Chrome issue https://github.com/BKWLD/decoy/issues/239
				%a.btn.btn-sm.outline.search-clear.js-tooltip(data-container=".full-header .btn-toolbar" title="Reset search")
					.glyphicon.glyphicon-ban-circle

-# Search UI
!=View::make('decoy::shared.list._search', $__data)


