<?php namespace Bkwld\Decoy\Auth;

// Dependencies
use App;
use Config;
use Cartalyst\Sentry\Users\Eloquent\User;

class SentryUser extends User {

	/**
	 * Disable the checkPersistCode() function when not on a live, production 
	 * site.  This check is the one that boots some if they log into the using
	 * the same user as someone with an active session on a different ip
	 * @param  string  $persistCode
	 * @return bool
	 */
	public function checkPersistCode($persistCode) {
		if (Config::get('site.live') === false || App::environment() != 'production') return true;
		else return parent::checkPersistCode($persistCode);
	}
	
}