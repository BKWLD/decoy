<?php namespace Bkwld\Decoy\Controllers;

// Dependencies
use App;
use Bkwld\Decoy\Models\Element;
use Bkwld\Decoy\Collections\Elements as ElementsCollection;
use Bkwld\Library\Utils\File;
use Cache;
use Config;
use Decoy;
use Input;
use View;

/**
 * Render a form that allows admins to override language files
 */
class Elements extends Base {
	

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
				'element' => Decoy::el($key)->applyExtraConfig(),
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
			'content' => "<div id='response' data-key='{$element->key}'>{$element->value}</div>"
		]);
	}
	
	
}
