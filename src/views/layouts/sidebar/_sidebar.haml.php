-if(!App::make('decoy.auth')->check()) return;

.sidebar
	!= View::make('decoy::layouts.sidebar._account')->render()
	!= View::make('decoy::layouts.sidebar._nav')->render()
	
!= View::make('decoy::layouts.sidebar._bottom')->render()