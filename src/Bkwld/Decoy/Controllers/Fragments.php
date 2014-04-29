<?php namespace Bkwld\Decoy\Controllers;

// Dependencies
use Former;
use Input;
use Redirect;
use Str;
use URL;

/**
 * Render a form that allows admins to override language files
 */
class Fragments extends Base {
	
	/**
	 * All fragments view
	 */
	public function index() {
		Former::withRules(Model::rules());
		Former::populate(Model::values());
		$this->layout->nest('content', 'decoy::fragments.index', array(
			'fragments' => Model::organized(),
		));
	}
	
	/**
	 * Handle form post
	 */
	public function store() {

		// Merge files into non-files input such that it's nested
		// where you would expect the files to be.
		$input = array_replace_recursive(Input::get(), Input::file());
		
		// Loop through the input and check if the field is different from the language
		// file version
		foreach($input as $key => $val) {
			
			// Ignore any fields that lead with an underscore, like _token
			if (Str::is('_*', $key)) continue;
			
			// Create, update, or delete row from DB
			Model::store($key, $val);
			
		}
		
		// Redirect back to index
		return Redirect::to(URL::current());;
		
	}
	
	
}