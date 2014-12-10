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
use Redirect;
use Str;
use URL;
use View;

/**
 * Render a form that allows admins to override language files
 */
class Elements extends Base {
	
	protected $description = 'Copy, images, and files that aren\'t managed as part of an item in a list.';

	/**
	 * All elements view
	 *
	 * @param string $locale The locale to load from the DB
	 * @param string $tab A deep link to a specific tab.  Will get processed by JS
	 * @return Illuminate\Http\Response
	 */
	public function index($locale = null, $tab = null) {

		// If there are no locales, treat the first argument as the tab
		if (!($locales = Config::get('decoy::site.locales')) || empty($locales)) {
			$tab = $locale;
			$locale = null;
		
		// Otherwise, set a default locale if none was specified
		} elseif (!$locale) $locale = Decoy::defaultLocale();

		// Get all the elements for the current locale
		$elements = app('decoy.elements')->localize($locale)->hydrate(true);

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
			'elements' => $elements->asModels(),
			'locale' => $locale,
			'tab' => $tab,
		]);

		// Set the breadcrumbs NOT include the locale/tab
		$this->breadcrumbs([route('decoy::elements') => 'Elements']);
	}

	/**
	 * A helper function for rendering the list of fields
	 *
	 * @param Bkwld\Decoy\Models\Element $el
	 * @param string $key
	 * @return Former\Traits\Object
	 */
	public static function renderField($el, $key = null) {
		if (!$key) $key = $el->inputName();
		switch($el->type) {
			case 'text': return Former::text($key, $el->label)->blockHelp($el->help);
			case 'textarea': return Former::textarea($key, $el->label)->blockHelp($el->help);
			case 'wysiwyg': return Former::textarea($key, $el->label)->addClass('wysiwyg')->blockHelp($el->help);
			case 'image': return Former::image($key, $el->label)->blockHelp($el->help);
			case 'file': return Former::upload($key, $el->label)->blockHelp($el->help);
			case 'checkbox': return Former::checkbox($key, false)->checkboxes(array("<b>{$el->label}</b>" => array('name' => $key, 'value' => 1)))->blockHelp($el->help);
			/**
			 * Not ported yet from Frags:
			 */
			// case 'video-encoder': return Former::videoEncoder($key, $el->label)->blockHelp($el->help);
			// case 'belongs_to': return Former::belongsTo($key, $el->label)->route($el->value)->blockHelp($el->help);
		}
	}

	/**
	 * Handle form post
	 *
	 * @param string $locale The locale to assign to it
	 * @return Illuminate\Http\Response
	 */
	public function store($locale = null) {

		// Get the default locale
		if (!$locale) $locale = Decoy::defaultLocale();

		// Get all the elements as models
		$elements = app('decoy.elements')
			->localize($locale)
			->hydrate()
			->asModels();

		// Merge the input into the elements and save them.  Key must be converted back
		// from the | delimited format necessitated by PHP
		$elements->each(function(Element $el) use ($locale) {

			// Check if the model is dirty, manually.  Laravel's performInsert()
			// doesn't do this, thus we must check ourselves.  We're removing the 
			// carriage returns because YAML won't include them and all multiline YAML
			// config values were incorrectly being returned as dirty.
			$key = $el->inputName();
			$value = str_replace("\r", '', Input::get($key));
			if ($value == $el->value && !Input::hasFile($key)) return;

			// Files are managed manually here, don't do the normal Decoy Base Model
			// file handling.  It doesn't work here because Decoy expects the Input to
			// contain fields for a single model instance.  Whereas Elements manages many
			// model records at once.
			$el->auto_manage_files = false;

			// Save it
			$el->exists = app('decoy.elements')->keyUpdated($el->key);
			$el->value = $el->saveFile($key) ?: $value;
			$el->locale = $locale;
			$el->save();
		});

		// Clear the cache
		$elements->clearCache();

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
				'element' => app('decoy.elements')
					->localize(Decoy::locale())
					->hydrate(true)
					->get($key),
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
		$elements = app('decoy.elements')->localize(Decoy::locale());

		// If the value has changed, update or an insert a record in the database.
		$el = Decoy::el($key);
		$value = Input::get('value');
		if ($value != $el->value || Input::hasFile('value')) {

			// Making use of the model's exists property to trigger Laravel's
			// internal logic.
			$el->exists = $elements->keyUpdated($el->key);

			// Save it.  Files will be automatically attached via model callbacks
			$el->value = $value;
			$el->locale = Decoy::locale();
			$el->save();

			// Clear the cache
			$elements->clearCache();
		}
		
		// Return the layout with JUST a script variable with the element value
		// after saving.  Thus, post any saving callback operations.
		return View::make('decoy::layouts.blank', [
			'content' => "<div id='response' data-key='{$key}'>{$el}</div>"
		]);
	}
	
	
}
