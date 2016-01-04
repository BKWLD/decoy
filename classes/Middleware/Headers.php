<?php namespace Bkwld\Decoy\Middleware;

// Deps
use Closure;

/**
 * Add simple headers to all requests
 */
class Headers {

	/**
	* Run the request filter.
	*
	* @param  Illuminate\Http\Request  $request
	* @param  Closure  $next
	* @return mixed
	*/
	public function handle($request, Closure $next) {
		$response = $next($request);

		// Tell IE that we're compatible so it doesn't show the compatbility checkbox
		$response->header('X-UA-Compatible', 'IE=Edge');

		// Return the response
		return $response;
	}

}
