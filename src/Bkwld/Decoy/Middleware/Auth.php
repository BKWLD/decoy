<?php namespace Bkwld\Decoy\Middleware;

// Deps
use Closure;

/**
 * Endorce access restrictions 
 */
class Auth {

	/**
	* Run the request filter.
	*
	* @param  \Illuminate\Http\Request  $request
	* @param  \Closure  $next
	* @return mixed
	*/
	public function handle($request, Closure $next) {

		// dd('In here');

		return $next($request);
	}

}