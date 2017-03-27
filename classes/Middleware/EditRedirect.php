<?php

namespace Bkwld\Decoy\Middleware;

use Closure;
use Redirect;

/**
 * Redirect old style edit links to the new /edit route
 */
class EditRedirect
{
    /**
    * Run the request filter.
    *
    * @param  Illuminate\Http\Request  $request
    * @param  Closure  $next
    * @return mixed
    */
    public function handle($request, Closure $next)
    {
        // If GET request ends in a number, redirect
        if ($request->method() == 'GET'
            && ($url = $request->url())
            && preg_match('#/\d+$#', $url)) {
            return Redirect::to($url.'/edit');
        }

        // Chain
        return $next($request);
    }
}
