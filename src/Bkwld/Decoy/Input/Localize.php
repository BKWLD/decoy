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
	 * The model class being localized
	 *
	 * @var string
	 */
	protected $model;

	/**
	 * The model instance being localized
	 */
	protected $item;

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
	 * Render the sidebar, "Localize" UI
	 * 
	 * @return string
	 */
	public function __toString() {
		return (string) View::make('decoy::shared.form.relationships._localize', [
			'model' => $this->model,
			'item' => $this->item,
			'localize' => $this,
		]);
	}

}