<?php namespace Bkwld\Decoy\Models;

// Dependencies
use Illuminate\Support\Collection;

/**
 * Represents an indivudal Element instance, hydrated with the merge of
 * YAML and DB Element sources
 */
class Element extends Base {

	/**
	 * The primary key for the model.
	 *
	 * @var string
	 */
	protected $primaryKey = 'key';

	/**
	 * No timestamps necessary
	 *
	 * @var bool
	 */
	public $timestamps = false;

	/**
	 * Hydrate with additional config options.  Also, make sure to only
	 * store once to reduce lookups.
	 *
	 * @return void 
	 */
	public function applyExtraConfig() {

		// If a label attribute is set, then we've already applied extra configs
		if (array_key_exists('label', $this->attributes)) return;

		// ... Else, lookup additional attributes from the YAML and apply them
		$this->setRawAttributes(

			// Parse the YAML, get this element, and merge it's fields with the current key
			array_merge(
				Collection::make(app('decoy.elements')
				->assocConfig(true))
				->get($this->key),

				// Preserve the key and value.  We don't want to replace the value from DB
				// with one from YAML
				['key' => $this->key, 'value' => $this->value])

		// Sync attributes, meaning, the model doesn't think it needs to save
		, true);

		// Enable chaining
		return $this;
	}

	/**
	 * Switch between different formats when rendering to a view
	 *
	 * @return string 
	 */
	public function format() {
		switch($this->type) {
			default: return $this->value;
		}
	}

	/**
	 * Render the element in a view
	 *
	 * @return string 
	 */
	public function __toString() {
		return $this->format();

	}

}