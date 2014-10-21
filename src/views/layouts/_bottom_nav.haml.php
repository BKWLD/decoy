-$auth = App::make('decoy.auth')

-# Holder for the ajax bar, logout, and view public site
.bottom-nav
	-# Add AJAX progress indicator
	!= View::make('decoy::layouts._ajax_progress')

	.controls
		%a.logout(href=$auth->logoutUrl()) Logout
		%a.public-site.glyphicon.glyphicon-new-window(href="/" title="View Public Site")	