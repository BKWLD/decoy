<?php namespace Bkwld\Decoy\Observers;

// Deps
use Redirect;
use Bkwld\Decoy\Models\RedirectRule;

/**
 * Redirect on 404 using CRUDed redirect rules
 */
class NotFound {

	/**
	 * Redirect on 404 using CRUDed redirect rules
	 * 
	 * @return Illuminate\Http\RedirectResponse|void
	 */
	static public function handle() {
		if ($rule = RedirectRule::matchFromWithRequest()->first()) {
			return Redirect::to($rule->to, $rule->code);
		}
	}

}