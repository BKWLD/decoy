-# Holder for the ajax bar, logout, and view public site
.bottom-nav
	-# Add AJAX progress indicator
	!= View::make('decoy::layouts.sidebar._progress')->render()

	.controls
		.left
			%a.logout(href=App::make('decoy.auth')->logoutUrl()) 
				%span.glyphicon.glyphicon-log-out
				Logout
			.subtitle
				%span.glyphicon.glyphicon-heart
				Decoy 4.9 by
				%a(href="http://bkwld.com" target="_blank") BKWLD
		%a.right(href="/")
			.glyphicon.glyphicon-globe
			.subtitle Open site

		