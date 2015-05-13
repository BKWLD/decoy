-if(!App::make('decoy.auth')->check()) return;

.sidebar
	!= View::make('decoy::layouts.sidebar._account')
	!= View::make('decoy::layouts.sidebar._nav')
	
!= View::make('decoy::layouts.sidebar._bottom')