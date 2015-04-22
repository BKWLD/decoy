<?php namespace Bkwld\Decoy\Observers;

// Deps
use Config;
use Bkwld\Decoy\Exceptions\ValidationFail;
use Bkwld\Decoy\Models\Base;
use Illuminate\Validation\Validator;
use Log;
use Request;
use Redirect;
use Response;

/**
 * Handle failed validations by redirecting or returning a special response
 */
class Validation {

	/**
	 * Handle the validation failure exceptions
	 * 
	 * @param  Bkwld\Decoy\Exceptions\ValidationFail $e
	 * @return Illuminate\Http\Response
	 */
	public function onFail(ValidationFail $e) {

		// Log validation errors so Reporter will output them
		if (Config::get('app.debug')) Log::debug(print_r($e->validation->messages(), true));
		
		// Respond
		if (Request::ajax()) {
			return Response::json($e->validation->messages(), 400);
		} else {
			return Redirect::to(Request::path())->withInput()->withErrors($e->validation);
		}
	}

}