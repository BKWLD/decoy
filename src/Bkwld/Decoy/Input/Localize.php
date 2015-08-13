<?php namespace Bkwld\Decoy\Input;

// Dependencies
use Config;
use View;

/**
 * Methods to assit in the creation of the localize sidebar UI
 * and the locale form field
 */
class Localize {

	/**
	 * The model instance being localized
	 */
	protected $item;

	/**
	 * The model class being localized
	 *
	 * @var string
	 */
	protected $model;

	/**
	 * Other localizations for the `$item`
	 */
	protected $other_localizations;

	/**
	 * The title of this model, from the controller
	 *
	 * @var string
	 */
	protected $title;

	/**
	 * Store a model instance
	 *
	 * @param Illuminate\Database\Eloquent\Model $item 
	 * @return $this 
	 */
	public function item($item) {
		if (!$this->model) $this->model = get_class($item);
		$this->item = $item;
		return $this;
	}

	/**
	 * Store the model class name
	 *
	 * @param string $model 
	 * @return $this 
	 */
	public function model($model) {
		$this->model = $model;
		return $this;
	}

	/**
	 * The title of this model, from the controller
	 *
	 * @param string $title 
	 * @return $this 
	 */
	public function title($title) {
		$this->title = $title;
		return $this;
	}

	/**
	 * Check if the localize UI should be displayed
	 * 
	 * @return boolean 
	 */
	public function hidden() {
		$class = $this->model; // Must be a local var to test
		return count(Config::get('decoy::site.locales')) <= 1 // There aren't multiple locales specified
			|| ($this->item && !$this->item->locale) // We're editing a model with no locale attribute
			|| $class::$localizable === false // The model has been set to NOT be localizable
			|| !Config::get('decoy::site.auto_localize_root_models') // We're not auto localizing at all
			|| app('decoy.wildcard')->detectParent(); // The're viewing a child model
	}

	/**
	 * Get a hash of locales that are available for the item
	 *
	 * @return array 
	 */
	public function localizableLocales() {
		return array_diff_key( // Keep only locales that don't exist in
			Config::get('decoy::site.locales'),
			array_flip($this->other()->lists('locale')), // ... the locales of other localizations
			[$this->item->locale => null] // ... and this locale
		); 
	}

	/**
	 * Get other localizations, storing them internally for use in multiple places
	 *
	 * @return Illuminate\Database\Eloquent\Collection
	 */
	public function other() {
		if ($this->other_localizations === null) {
			$this->other_localizations = $this->item->otherLocalizations()->get();
		}
		return $this->other_localizations;
	}

	/**
	 * Render the sidebar, "Localize" UI
	 * 
	 * @return string
	 */
	public function __toString() {
		return View::make('decoy::shared.form.relationships._localize', [
			'model' => $this->model,
			'item' => $this->item,
			'title' => $this->title,
			'localize' => $this,
		])->render();
	}

}