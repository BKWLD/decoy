<?php namespace Bkwld\Decoy\Collections;

// Dependencies
use Bkwld\Decoy\Exception;
use Bkwld\Decoy\Models\Element;
use Bkwld\Library\Utils;
use Illuminate\Cache\Repository;
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
	 * Get an element given it's key
	 *
	 * @param string $key 
	 * @return Bkwld\Decoy\Models\Element
	 */
	public function get($key, $default = null) {

		// Build the colletion if isn't already defined
		if ($this->isEmpty()) $this->hydrate();

		// Build an element from the item in the collection or throw an
		// exception if the key isn't valid
		if (!$this->has($key)) {
			if ($default) return $default;
			else {
				\Log::debug('Keys are: ',$this->keys());
				throw new Exception("Element key '{$key}' is not declared in elements.yaml.");
			}
		} return new Element(array_merge($this->items[$key], ['key' => $key]));

	}

	/**
	 * Populate the Collection from the Cache or build the cache if it isn't set yet
	 *
	 * @return void 
	 */
	protected function hydrate() {
		if ($data = $this->cache->get(self::CACHE_KEY)) {
			$this->items = $data;
		} else {
			$this->items = $this->mergeSources();
			$this->cache->forever(self::CACHE_KEY, $this->items);
		}
	}

	/**
	 * Merge database records and config file into a single, flat associative array 
	 *
	 * @return void 
	 */
	protected function mergeSources() {
		return array_merge($this->assocConfig(), $this->assocAdminChoices());
	}

	/**
	 * Massage the YAML config file into a single, flat associative array 
	 *
	 * @param boolean $include_extra Include attibutes that are only needed by Admin UIs
	 * @return array
	 */
	public function assocConfig($include_extra = false) {

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
					else $value = $field_data;

					// Build the value array
					$el = ['type' => $type, 'value' => $value];
					if ($include_extra) {
						$this->mergeExtra($el, $field, $field_data);
						$this->mergeExtra($el, $section, $section_data, 'section_');
						$this->mergeExtra($el, $page, $page_data, 'page_');
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
		if (isset($data['help'])) $el[$prefix.'help'] = $data['help'];
	}

	/**
	 * Get admin overrides to Elements from the databse
	 *
	 * @return array 
	 */
	protected function assocAdminChoices() {

		// Convert models to simple arrays with the type and value
		return array_map(function(Element $element) {
			$element->setVisible(['type', 'value']);
			return $element->toArray();

		// .. from a dictionary of ALL elements
		}, Element::all()->getDictionary());
	}

	/**
	 * Load the config file and store it internally
	 *
	 * @return void 
	 */
	protected function loadConfig() {
		$file = app_path().'/config/packages/bkwld/decoy/elements.yaml';
		if (!is_readable($file)) throw new Exception("Elements.yaml doesn't exist or isn't readable");
		$this->config = $this->yaml_parser->parse(file_get_contents($file));
	}

}