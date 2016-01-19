<?php namespace Bkwld\Decoy\Input;

// Dependencies
use Bkwld\Decoy\Models\Base as BaseModel;
use Validator;
use Bkwld\Decoy\Exceptions\ValidationFail;

class ModelValidator {

	/**
	 * Validatea model, firing Decoy events
	 *
	 * @param Base\Model $data
	 * @param array A Laravel rules array. If null, will be pulled from model
	 * @param array $messages Special error messages
	 * @return Validator
	 *
	 * @throws ValidationFail
	 */
	public function validate(BaseModel $model, $rules = null, $messages = []) {

		// Get the data to validate
		$data = $model->getAttributes();

		// Get rules from model
		if ($rules === null) $rules = $model::$rules;

		// Build the validation instance and fire the intiating event.
		$validation = Validator::make($data, $rules, $messages);
		$model->fireDecoyEvent('validating', [$model, $validation]);

		// Run the validation.  If it fails, throw an exception that will get handled
		// by Middleware.
		if ($validation->fails()) throw new ValidationFail($validation);

		// Fire completion event
		$model->fireDecoyEvent('validated', [$model, $validation]);
		return $validation;
	}
}
