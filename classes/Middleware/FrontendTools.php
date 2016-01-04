<?php namespace Bkwld\Decoy\Middleware;

// Deps
use Closure;
use Decoy;
use View;

/**
 * Add markup needed for Decoy's frontend tools 
 */
class FrontendTools {

	/**
	* Run the request filter.
	*
	* @param  Illuminate\Http\Request  $request
	* @param  Closure  $next
	* @return mixed
	*/
	public function handle($request, Closure $next) {

		// Apply after
		$response = $next($request);

		// Conditionally add the frontend markup
		if (app('decoy.auth')->check() // Require an authed admin
			&& !Decoy::handling() // Don't apply to the backend
			&& ($content = $response->getContent()) // Get the whole response HTML
			&& is_string($content)) { // Double check it's a string
			
			// Add the Decoy Frontend markup to the page right before the closing body tag
			$content = str_replace('</body>', 
				View::make('decoy::frontend._embed')->render().'</body>', 
				$content);
			$response->setContent($content);
		}

		// Chain
		return $response;

	}

}