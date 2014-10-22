<?php namespace Bkwld\Decoy\Controllers;

// Dependencies
use App;
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
	public function index($tab=null) {
		
		// Get all of the fragment data organized by tab titles
		$data = Model::organized();

		// If handling a deep link to a tab, verify that the passed tab
		// slug is a real key in the data.  Else 404.
		if ($tab && !in_array($tab, array_map(function($title) {
			return Str::slug($title);
		}, array_keys($data)))) App::abort(404);

		// Render the view
		Former::withRules(Model::rules());
		Former::populate(Model::values());
		$this->populateView('decoy::fragments.index', [
			'fragments' => $data,
		]);
	}
	
	/**
	 * Handle form post
	 */
	public function store() {

		// Merge files into non-files input such that it's nested
		// where you would expect the files to be.
		$input = array_replace_recursive(Input::get(), array_filter(Input::file()));
		
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
