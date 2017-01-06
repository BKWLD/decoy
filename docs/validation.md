# Validation

Validation rules are stored in the model in the static `$rules` property.  The syntax is just  normal [Laravel validation](https://laravel.com/docs/5.3/validation#available-validation-rules), keyed to your column name.

## Validating images

To support validating images (which are stored in another table), Decoy adds support for "dot" notation in the rules array for specifying the image to be validated.

## Custom validation

An easy way to add custom validation to models is by specifying an `onValidating` method, taking the `$validation` object, adding errors to it, and returning it.  Special logic in the Base Controller's validate method will see the returned `Validator` and its errors and respond appropriately.

	public function onValidating($validation) {
		$validation->errors()->add('last_name', 'This last name sucks');
		throw new \Bkwld\Decoy\Exceptions\ValidationFail($validation);
	}

Here's an example of how to set unique exceptions for the current model instance when updating.  This example assumes that the `unique` rule is the 3rd (the 2nd offset) rule defined for the `name` field.  This allows us to just append the id onto it.


	public function onValidating($validation) {
		if ($this->exists) {
			$rules = $validation->getRules();
			$rules['name'][2] .= ','.$this->getKey();
			$validation->setRules($rules);
		}
	}
