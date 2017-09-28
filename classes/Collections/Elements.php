<?php

namespace Bkwld\Decoy\Collections;

// Deps
use App;
use Bkwld\Library\Utils;
use Bkwld\Decoy\Exceptions\Exception;
use Bkwld\Decoy\Models\Element;
use Cache;
use Illuminate\Database\Eloquent\Collection as ModelCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Log;
use Symfony\Component\Yaml\Yaml;

/**
 * Produces a store of all the site Elements
 */
class Elements extends Collection
{

    /**
     * The cache key used for the Elements collection
     *
     * @var string
     */
    protected $cache_key = 'decoy.elements.data';

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
     *
     * @var string
     */
    protected $locale;

    /**
     * The model class that should be used for each Element instance
     *
     * @var string
     */
    protected $model = null;


    /**
     * An array of Element keys that have been persisted in the DB
     *
     * @var null | array
     */
    protected $updated_items = null;

    /**
     * Map the items into a collection of Element instances
     *
     * @return Bkwld\Decoy\Collections\Elements
     */
    public function asModels()
    {
        $this->hydrate();

        return new ModelCollection(array_map(function ($element, $key) {

            // Add the key as an attribute
            return new $this->model(array_merge($element, ['key' => $key]));
        }, $this->all(), array_keys($this->items)));
    }

    /**
     * Get an element given it's key
     *
     * @param  string  $key
     * @return Bkwld\Decoy\Models\Element
     */
    public function get($key, $default = null)
    {
        $this->hydrate();

        // Create an Element instance using the data for the key
        if ($this->has($key)) {
            return new $this->model(array_merge($this->items[$key], ['key' => $key]));
        }

        // If the key doesn't exist but a default was passed in, return it
        if ($default) {
            return $default;
        }

        // if the key doesn't exist, but running locally, throw an exception
        if (App::isLocal()) {
            throw new Exception("Element key '{$key}' is not declared in elements.yaml.");
        }

        // Otherwise, like if key doesn't exist and running on production,
        // return an empty Element, whose ->toString() will return an empty string.
        Log::error("Element key '{$key}' is not declared in elements.yaml.");
        return new $this->model();
    }

    /**
     * Get a number of elements at once by passing in a first or second level
     * depth key.  Like just `'homepage.marquee'`
     *
     * @param  string $prefix Any leading part of a key
     * @param  array  $crops  Assoc array with Element partial keys for ITS keys
     *                        and values as an arary of crop()-style arguments
     * @return array
     */
    public function getMany($prefix, $crops = [])
    {
        // Get all of the elements matching the prefix with dot-notated keys
        $dotted = $this
            ->hydrate()
            ->filter(function($val, $key) use ($prefix) {
                return starts_with($key, $prefix);

            // Loop through all matching elements
            })->map(function($val, $key) use ($prefix, $crops) {

                // Resolve the key using the Element helper so that we get an
                // actual Element model instance.
                $el = $this->get($key);
                $value = $el->value();

                // Check if the element key is in the $crops config.  If so,
                // return the croopped image instructions.
                $crop_key = substr($key, strlen($prefix) + 1);
                if (isset($crops[$crop_key])) {

                    // Handle models returned from BelongsTo fields
                    if (is_a($value, Base::class)) {
                        $func = [$value, 'withDefaultImage'];
                        return call_user_func_array($func, $crops[$crop_key]);
                    }

                    // Otherwise, use the crop helper on the Element model
                    return call_user_func_array([$el, 'crop'], $crops[$crop_key]);
                }

                // If no crops were defined, return the value
                return (string) $value;

            // Convert the collection to an array
            })->all();

        // Make a multidimensionsl array from the dots, stripping the prefix
        // from the  keys.  Then return it.
        $multi = [];
        $len = strlen($prefix);
        foreach($dotted as $key => $val) {
            array_set($multi, trim(substr($key, $len), '.'), $val);
        }
        return $multi;
    }

    /**
     * Set the locale that should be used for this collection
     *
     * @param  string $locale
     * @return $this
     */
    public function localize($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Populate the Collection from the Cache or build the cache if it isn't set
     * yet. But only if not on a local environment (as new Elements are added,
     * you would have to keep re-clearing the cache)
     *
     * @return $this
     */
    public function hydrate($include_extra = false)
    {
        // If including extra YAML config vars, neither use the cache NOR allow
        // the cache to be saved with it
        if ($include_extra && !$this->has_extra) {
            $this->has_extra = true;
            $this->items = $this->mergeSources();
            return $this;
        }

        // If already hydrated, do nothing
        if (!$this->isEmpty()) {
            return $this;
        }

        // If running locally, don't use or store the cache
        if (App::isLocal()) {
            $this->items = $this->mergeSources();
            return $this;
        }

        // Else, use the cache if it exists or generate the cache
        if ($data = Cache::get($this->cacheKey())) {
            $this->items = $data;
        } else {
            $this->items = $this->mergeSources();
            Cache::forever($this->cacheKey(), $this->items);
        }

        // Allow chain
        return $this;
    }

    /**
     * Build the cache key using the locale
     *
     * @return string
     */
    protected function cacheKey()
    {
        if ($this->locale) {
            return $this->cache_key.'.'.$this->locale;
        }

        return $this->cache_key;
    }

    /**
     * Set the cache key
     *
     * @param  string $key
     * @return $this
     */
    public function setCacheKey($key)
    {
        $this->cache_key = $key;

        return $this;
    }

    /**
     * Clear the cache
     *
     * @return $this
     */
    public function clearCache()
    {
        Cache::forget($this->cacheKey());

        return $this;
    }

    /**
     * Clear the internal store as well as the cache, in effect
     * totally resetting hydration
     *
     * @return $this
     */
    public function reset()
    {
        $this->items = [];
        $this->clearCache();

        return $this;
    }

    /**
     * Merge database records and config file into a single, flat associative array.
     *
     * @return void
     */
    protected function mergeSources()
    {
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
     * @param  boolean $include_extra Include attibutes that are only needed by Admin UIs
     * @return array
     */
    public function assocConfig($include_extra = false)
    {
        // Load the config data if it isn't already
        if (!$this->config) {
            $this->loadConfig();
        }

        // Loop through the YAML config and make flattened keys.  The ternary
        // operators in here allow a shorthand version of the YAML config as
        // described in the docs.
        $config = [];
        foreach ($this->config as $page => $page_data) {
            foreach (isset($page_data['sections']) ? $page_data['sections'] : $page_data as $section => $section_data) {
                foreach (isset($section_data['fields']) ? $section_data['fields'] : $section_data as $field => $field_data) {

                    // Determine the type of field
                    $field_parts = explode(',', $field);
                    $field = $field_parts[0];
                    if (count($field_parts) == 2) {
                        $type = $field_parts[1];
                    } // If concatted onto the field name
                    elseif (is_array($field_data) && isset($field_data['type'])) {
                        $type = $field_data['type'];
                    } // Explicitly set
                    else {
                        $type = 'text';
                    } // Default type

                    // Determine the value
                    if (is_array($field_data) && array_key_exists('value', $field_data)) {
                        $value = $field_data['value'];
                    } elseif (is_scalar($field_data)) {
                        $value = $field_data;
                    } // String, boolean, int, etc
                    else {
                        $value = null;
                    }

                    // Build the value array
                    $el = ['type' => $type, 'value' => $value];
                    if ($this->has_extra || $include_extra) {
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
     * @param array  $el     The element data that is being merged into, passed by reference
     * @param mixed  $data   The data for a level in the Elements YAML config
     * @param string $prefix A prefix to append to the beginning of the key being set on $el
     */
    protected function mergeExtra(&$el, $key, $data, $prefix = null)
    {
        // Don't add extra if this in the 1st or 2nd depth (which means there is a prefix)
        // and there is no node for the children.  This prevents a FIELD named "label" to be
        // treated as the the label for it's section.
        $skip = $prefix && empty($data['sections']) && empty($data['fields']);

        // Fields
        if (isset($data['label']) && $data['label'] === false) {
            $el[$prefix.'label'] = false;
        } else {
            $el[$prefix.'label'] = empty($data['label']) || $skip ? Utils\Text::titleFromKey($key) : $data['label'];
        }
        $el[$prefix.'help'] = empty($data['help']) || $skip ? null : $data['help'];

        // Used by radio, select, and checkboxes types
        $el[$prefix.'options'] = empty($data['options']) || $skip ? null : $data['options'];

        // Used by belongs to but maybe others in the future
        $el[$prefix.'class'] = empty($data['class']) || $skip ? null : $data['class'];

        // Used by videoEncoder
        $el[$prefix.'preset'] = empty($data['preset']) || $skip ? null : $data['preset'];
    }

    /**
     * Get admin overrides to Elements from the databse
     *
     * @return array
     */
    protected function assocAdminChoices()
    {
        // Build the query
        $query = call_user_func([$this->model, 'query']);
        if ($this->locale) {
            $query->localize($this->locale);
        }

        // Convert models to simple array
        $elements = array_map(function (Element $element) {

            // Don't need the key as an attribute because of the dictionary conversion
            $ar = array_except($element->toArray(), ['key']);

            // Restore relationships
            $ar['images'] = $element->images;

            return $ar;

        // .. from a dictionary of ALL elements for the locale
        }, $query->get()->getDictionary());

        // Store the keys of all these elements so we can keep track of which
        // Elements "exist"
        $this->updated_items = array_keys($elements);

        // Return the elements
        return $elements;
    }

    /**
     * Return the YAML config, as associative array
     *
     * @return array
     */
    public function getConfig()
    {
        if (!$this->config) {
            $this->loadConfig();
        }

        return $this->config;
    }

    /**
     * Load the config file and store it internally
     *
     * @return void
     */
    protected function loadConfig()
    {
        // Start with empty config
        $this->config = [];

        // Build a lit of all the paths
        $dir = config_path('decoy').'/';
        $files = [];
        if (is_readable($dir.'elements.yaml')) {
            $files[] = $dir.'elements.yaml';
        }
        if (is_dir($dir.'elements')) {
            $files = array_merge($files, glob($dir.'elements/*.yaml'));
        }
        if (!count($files)) {
            return;
        }

        // Loop though config files and merge them together
        foreach ($files as $file) {

            // If an array found ...
            if (($config = Yaml::parse(file_get_contents($file)))
                && is_array($config)) {

                // Merge it in
                $this->config = array_replace_recursive($this->config, $config);
            }
        }
    }

    /**
     * Check if a key has been stored in the database
     *
     * @param  string  $key The key of an element
     * @return boolean
     */
    public function keyUpdated($key)
    {
        if ($this->updated_items === null) {
            $this->assocAdminChoices();
        }

        return in_array($key, $this->updated_items);
    }

    /**
     * Filter the elements to only those allowed in the provided pages
     *
     * @param  array $pages
     * @return $this
     */
    public function onlyPages($pages)
    {
        $this->items = array_filter($this->items, function ($element) use ($pages) {
            return in_array(Str::slug($element['page_label']), $pages);
        });

        return $this;
    }

    /**
     * Return the validation rules for the items.  Convert the keys to the
     * expected input style
     *
     * @return array An array of validation rules, keyed to element keys
     */
    public function rules()
    {
        $rules = [];
        foreach ($this->assocConfig(true) as $key => $data) {
            if (empty($data['rules'])) {
                continue;
            }
            $rules[str_replace('.', '|', $key)] = $data['rules'];
        }

        return $rules;
    }

    /**
     * Return key-value pairs for use by former to populate the fields.  The
     * keys must be converted to the Input safe variant.
     *
     * @return array
     */
    public function populate()
    {
        return array_combine(array_map(function ($key) {
            return str_replace('.', '|', $key);
        }, $this->keys()->all()), $this->pluck('value')->all());
    }

    /**
     * Get the model instance being used
     *
     * @return string
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Replace the model class being used (via an instance) and listen for updates.
     *
     * @param  string $class Class name of Elements or a subclasss
     * @return $this
     */
    public function setModel($class)
    {
        $this->model = $class;
        call_user_func([$class, 'created'], [$this, 'onModelUpdate']);
        call_user_func([$class, 'updated'], [$this, 'onModelUpdate']);

        return $this;
    }

    /**
     * When a model is updated, update the corresponding key-val pair
     *
     * @param  Bkwld\Decoy\Models\Element $element
     * @return void
     */
    public function onModelUpdate($element)
    {
        $this->items[$element->getKey()]['value'] = $element->getAttribute('value');
    }
}
