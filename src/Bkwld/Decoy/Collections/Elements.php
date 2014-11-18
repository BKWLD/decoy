<?php namespace Bkwld\Decoy\Collections;

// Dependencies
use App;
use Bkwld\Decoy\Exception;
use Bkwld\Decoy\Models\Element;
use Bkwld\Library\Utils;
use Illuminate\Cache\Repository;
use Illuminate\Database\Eloquent\Collection as ModelCollection;
use Illuminate\Support\Collection;
use Symfony\Component\Yaml\Parser;

/**
 * Produces a store of all the site Elements
 */
class Elements extends Collection {

	/**
	 * The cache key used for the Elements collection
	 */
	const CACHE_KEY = 'decoy.elements.data';

	/**
	 * The parse YAML contents
	 * 
	 * @var array
	 */
	protected $config;

	/**
	 * Store whether this collection includes extra config from the YAML
	 *
	 * @var boolean
	 */
	protected $has_extra = false;

	/**
	 * Store the locale that was used to hydrate the collection
	 */
	protected $locale;

	/**
	 * An array of Element keys that have been persisted in the DB
	 *
	 * @var null | array
	 */
	protected $updated_items = null;

	/**
	 * Dependency injection
	 * 
	 * @param Symfony\Component\Yaml\Parser $yaml_parser 
	 * @param Bkwld\Decoy\Models\Element $model
	 * @param  Illuminate\Cache\Repository $cache
	 */
	public function __construct(Parser $yaml_parser, Element $model, Repository $cache) {
		$this->yaml_parser = $yaml_parser;
		$this->model = $model;
		$this->cache = $cache;
	}

	/**
	 * Map the items into a collection of Element instances
	 *
	 * @return Bkwld\Decoy\Collections\Elements
	 */
	public function asModels() {
		$this->hydrate();
		return new ModelCollection(array_map(function($element, $key) {
			return new Element(array_merge($element, ['key' => $key])); // Add the key as an attribute
		}, $this->all(), array_keys($this->items)));
	}

	/**
	 * Get an element given it's key
	 *
	 * @param string $key 
	 * @return Bkwld\Decoy\Models\Element
	 */
	public function get($key, $default = null) {
		$this->hydrate();

		// Build an element from the item in the collection or throw an
		// exception if the key isn't valid
		if (!$this->has($key)) {
			if ($default) return $default;
			else {
				\Log::debug('Keys are: ',$this->keys());
				throw new Exception("Element key '{$key}' is not declared in elements.yaml.");
			}

		// Add the key as an attribute
		} else return new Element(array_merge($this->items[$key], ['key' => $key])); 

	}

	/**
	 * Set the locale that should be used for this collection
	 *
	 * @param string $locale 
	 * @return $this
	 */
	public function localize($locale) {
		$this->locale = $locale;
		return $this;
	}

	/**
	 * Populate the Collection from the Cache or build the cache if it isn't set yet.
	 * But only if not on a local environment (as new Elements are added, you would have
	 * to keep re-clearing the cache)
	 *
	 * @return $this 
	 */
	public function hydrate($include_extra = false) {
		
		// If including extra YAML config vars, neither use the cache NOR allow the cache
		// to be saved with it
		if ($include_extra && !$this->has_extra) {
			$this->has_extra = true;
			$this->items = $this->mergeSources();
			return $this;
		}

		// If already hydrated, do nothing
		if (!$this->isEmpty()) return $this;

		// If running locally, don't use or store the cache
		if (App::isLocal()) {
			$this->items = $this->mergeSources();
			return $this;
		}

		// Else, use the cache if it exists or generate the cache
		if ($data = $this->cache->get($this->cacheKey())) {
			$this->items = $data;
		} else {
			$this->items = $this->mergeSources();
			$this->cache->forever($this->cacheKey(), $this->items);
		}
		return $this;
	}

	/**
	 * Build the cache key using the locale
	 *
	 * @return string 
	 */
	protected function cacheKey() {
		if ($this->locale) return self::CACHE_KEY.'.'.$this->locale;
		else return self::CACHE_KEY;
	}

	/**
	 * Merge database records and config file into a single, flat associative array.
	 *
	 * @return void 
	 */
	protected function mergeSources() {
		$assoc = $this->assocConfig();
		return array_replace_recursive($assoc, 

			// Get only the databse records whose keys are present in the YAML.  This removes
			// entries that may be from older YAML configs.
			array_intersect_key($this->assocAdminChoices(), $assoc)
		);
	}

	/**
	 * Massage the YAML config file into a single, flat associative array 
	 *
	 * @param boolean $include_extra Include attibutes that are only needed by Admin UIs
	 * @return array
	 */
	protected function assocConfig($include_extra = false) {

		// Load the config data if it isn't already
		if (!$this->config) $this->loadConfig();

		// Loop through the YAML config and make flattened keys.  The ternary
		// operators in here allow a shorthand version of the YAML config as
		// described in the docs.
		$config = [];
		foreach($this->config as $page => $page_data) {
			foreach(isset($page_data['sections']) ? $page_data['sections'] : $page_data as $section => $section_data) {
				foreach(isset($section_data['fields']) ? $section_data['fields'] : $section_data as $field => $field_data) {

					// Determine the type of field
					$field_parts = explode(',', $field);
					$field = $field_parts[0];
					if (count($field_parts) == 2) $type = $field_parts[1]; // If concatted onto the field name
					elseif (is_array($field_data) && isset($field_data['type'])) $type = $field_data['type']; // Explicitly set
					else $type = 'text'; // Default type

					// Determine the value
					if (is_array($field_data) && array_key_exists('value', $field_data)) $value = $field_data['value'];
					elseif (is_string($field_data)) $value = $field_data;
					else $value = null;

					// Build the value array
					$el = ['type' => $type, 'value' => $value];
					if ($this->has_extra) {
						$this->mergeExtra($el, $field, $field_data);
						$this->mergeExtra($el, $section, $section_data, 'section_');
						$this->mergeExtra($el, $page, $page_data, 'page_');
						$el['rules'] = isset($field_data['rules']) ? $field_data['rules'] : null;
					}

					// Add the config
					$config["{$page}.{$section}.{$field}"] = $el;
				}
			}
		}

		// Return the flattened config
		return $config;
	}

	/**
	 * Add label and help to the element data for one of the levels
	 *
	 * @param array $el The element data that is being merged into, passed by reference
	 * @param mixed $data The data for a level in the Elements YAML config
	 * @param string $prefix A prefix to append to the beginning of the key being set on $el
	 */
	protected function mergeExtra(&$el, $key, $data, $prefix = null) {
		$el[$prefix.'label'] = isset($data['label']) ? $data['label'] : Utils\String::titleFromKey($key);
		$el[$prefix.'help'] = isset($data['help']) ? $data['help'] : null;
	}

	/**
	 * Get admin overrides to Elements from the databse
	 *
	 * @return array 
	 */
	protected function assocAdminChoices() {

		// Build the query
		$elements = Element::query();
		if ($this->locale) $elements->localize($this->locale);

		// Convert models to simple arrays with the type and value
		$elements = array_map(function(Element $element) {
			$element->setVisible(['type', 'value']);
			return $element->toArray();

		// .. from a dictionary of ALL elements for the locale
		}, $elements->get()->getDictionary());

		// Store the keys of all these elements so we can keep track of which
		// Elements "exist"
		$this->updated_items = array_keys($elements);

		// Return the elements
		return $elements;
	}

	/**
	 * Load the config file and store it internally
	 *
	 * @return void 
	 */
	protected function loadConfig() {

		// Build a lit of all the paths
		$dir = app_path().'/config/packages/bkwld/decoy/';
		$files = [];
		if (is_readable($dir.'elements.yaml')) $files[] = $dir.'elements.yaml';
		if (is_dir($dir.'elements')) $files = array_merge($files, glob($dir.'elements/*.yaml'));
		if (!count($files)) throw new Exception("No readable elements yaml files found");

		// Loop though config files and merge them together
		$this->config = [];
		foreach($files as $file) {

			// If an array found ...
			if (($config = $this->yaml_parser->parse(file_get_contents($file)))
				&& is_array($config)) {

				// Merge it in
				$this->config = array_replace_recursive($this->config, $config);
			}
		}
	}

	/**
	 * Check if a key has been stored in the database
	 *
	 * @param string $key The key of an element
	 * @return boolean 
	 */
	public function keyUpdated($key) {
		if ($this->updated_items === null) $this->assocAdminChoices();
		return in_array($key, $this->updated_items);
	}

	/**
	 * Return the validation rules for the items
	 *
	 * @return array An array of validation rules, keyed to element keys
	 */
	public function rules() {
		return array_combine($this->keys(), $this->lists('rules'));
	}
	
	/**
	 * Return key-value pairs for use by former to populate the fields.  The
	 * keys must be converted to the Input safe variant.
	 *
	 * @return array 
	 */
	public function populate() {
		return array_combine(array_map(function($key) {
			return str_replace('.', '|', $key);
		}, $this->keys()), $this->lists('value'));
	}

}