<?php namespace Bkwld\Decoy\Controllers;

// Dependencies
use App;
use Bkwld\Decoy\Models\Element;
use Bkwld\Decoy\Collections\Elements as ElementsCollection;
use Bkwld\Library\Utils\File;
use Cache;
use Config;
use Decoy;
use Former;
use Input;
use Str;
use View;

/**
 * Render a form that allows admins to override language files
 */
class Elements extends Base {
	
	protected $description = 'Copy, images, and files that aren\'t managed as part of an item in a list.';

	/**
	 * All fragments view
	 *
	 * @param string $tab A deep link to a specific tab.  Will get processed by JS
	 * @return Illuminate\Http\Response
	 */
	public function index($tab = null) {
		
		// Get all the elements
		$elements = app('decoy.elements')->hydrate(true);

		// If handling a deep link to a tab, verify that the passed tab
		// slug is a real key in the data.  Else 404.
		if ($tab && !in_array($tab, array_map(function($title) {
			return Str::slug($title);
		}, $elements->lists('page_label')))) App::abort(404);

		// Populate form
		Former::withRules($elements->rules());
		Former::populate($elements->populate());

		// Render the view
		$this->populateView('decoy::elements.index', [
			'elements' => $elements->allModels(),
		]);
	}

	/**
	 * Handle form post
	 *
	 * @return Illuminate\Http\Response
	 */
	public function store() {

		/*

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
		
		*/

		// Redirect back to index
		return Redirect::to(URL::current());;
		
	}

	/**
	 * Show the field editor form that will appear in an iframe on
	 * the frontend
	 * 
	 * @param  string $key A full Element key
	 * @return Illuminate\Http\Response
	 */
	public function field($key) {
		return View::make('decoy::layouts.blank')
			->nest('content', 'decoy::elements.field', [
				'element' => app('decoy.elements')->hydrate(true)->get($key),
			]);
	}

	/**
	 * Update a single field because of a frontend Element editor
	 * iframe post
	 * 
	 * @param  string $key A full Element key
	 * @return Illuminate\Http\Response
	 */
	public function fieldUpdate($key) {

		// If the value has changed, update or an insert a record in the database.
		$element = Decoy::el($key);
		if (Input::get('value') != $element->value || Input::hasFile('value')) {

			// Making use of the model's exists property to trigger Laravel's
			// internal logic.
			$element->exists = !empty(Element::find($key));

			// Save it.  Files will be automatically attached via model callbacks
			$element->value = Input::get('value');
			$element->save();

			// Clear the cache
			Cache::forget(ElementsCollection::CACHE_KEY);
		}
		
		// Return the layout with JUST a script variable with the element value
		// after saving.  Thus, post any saving callback operations.
		return View::make('decoy::layouts.blank', [
			'content' => "<div id='response' data-key='{$key}'>{$element->value}</div>"
		]);
	}
	
	
}
