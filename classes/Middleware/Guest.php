<?php

namespace Bkwld\Decoy\Middleware;

use Closure;

/**
 * Replicates the behavior of the RedirectIfAuthenticated middleware that
 * Laravel ships with.  It redirects logged in admins to their admin home page.
 */
class Guest
{
    /**
    * Handle an incoming request.
    *
    * @param  \Illuminate\Http\Request $request
    * @param  \Closure                 $next
    * @return mixed
    */
    public function handle($request, Closure $next)
    {
        // If logged in, redirect to user's home
        if (app('decoy.user')) {
            return redirect($this->getHome());
        }

        // No admim auth session
        return $next($request);
    }

    /**
     * Determine what the dashboard URL should be, where the user is redirected
     * after login.
     *
     * @return string
     */
    public function getHome()
    {
        // Vars
        $config = config('decoy.site.post_login_redirect');
        $auth = app('decoy.user');

        // Make the config optional
        if ($config) {

            // Support the config being a colsure
            if (is_callable($config)) {
                $config = call_user_func($config);
            }

            // Make sure the user has permission before redirecting
            if ($auth->can('read', $config)) {
                return $config;
            }
        }

        // If the user doesn't have permission, iterate through the navigation
        // options until one is permissible
        foreach ($this->getNavUrls() as $url) {
            if ($auth->can('read', $url)) {
                return $url;
            }
        }

        // Default to their account page, which all can access
        return $auth->getUserUrl();
    }

    /**
     * Return a flat list of all the URLs in the nav.  This doesn't include ones
     * automatically added by Decoy
     *
     * @param  array $nav
     * @return array
     */
    public function getNavUrls($nav = null)
    {
        // If no nav passed, as it would be for a sub navs, get the configed nav
        if (empty($nav)) {
            $nav = config('decoy.site.nav');
        }

        // Allow for the nav to be acallable
        if (is_callable($nav)) {
            $nav = call_user_func($nav);
        }

        // Loop through the nav
        $flat = [];
        foreach ($nav as $val) {
            if (is_array($val)) {
                $flat = array_merge($flat, $this->getNavUrls($val));
            } else {
                $flat[] = $val;
            }
        }

        return $flat;
    }
}
