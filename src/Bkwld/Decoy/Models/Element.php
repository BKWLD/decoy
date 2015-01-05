<?php namespace Bkwld\Decoy\Models;

// Dependencies
use Config;
use Bkwld\Library\Utils\File;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Str;

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
	 * Enforce the composite key while saving. Element has a composite primary
	 * key accross `key` and `locale`
	 * https://github.com/laravel/framework/issues/5355
	 *
	 * @param  Illuminate\Database\Eloquent\Builder  $query
	 * @return Illuminate\Database\Eloquent\Builder
	 */
	protected function setKeysForSaveQuery(Builder $query) {
		parent::setKeysForSaveQuery($query);
		$query->where('locale', '=', $this->locale);
		return $query;
	}

	/**
	 * Subclass setAttribute so that we can automatically set validation
	 * rules based on the Element type
	 * 
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	public function setAttribute($key, $value) {
		if ($key == 'type') { switch($value) {
			case 'image': self::$rules['value'] = 'image'; break;
			case 'file': self::$rules['value'] = 'file'; break;
			case 'video-encoder': self::$rules['value'] = 'video'; break;
		}}

		// Continue
		return parent::setAttribute($key, $value);
	}

	/**
	 * Format has been deprecated
	 *
	 * @return string 
	 */
	public function format() { return value(); }

	/**
	 * Format the value before returning it based on the type
	 *
	 * @return string 
	 */
	public function value() {
		
		// Must return a string
		if (empty($this->value)) return '';

		// Different outputs depending on type
		switch($this->type) {
			case 'boolean': return !empty($this->value);
			case 'image': return $this->copyImage();
			case 'textarea': return nl2br($this->value);
			case 'wysiwyg': return Str::startsWith($this->value, '<') ? $this->value : "<p>{$this->value}</p>";
			default: return $this->value;
		}
	}

	/**
	 * Check if the value looks like an image.  If it does, copy it to the uploads dir
	 * so Croppa can work on it and return the modified path
	 *
	 * @return string The new path (relative to uploads dir)
	 */
	protected function copyImage() {

		// Return nothing if empty
		if (!$this->value) return '';
		
		// All images must live in the /img (relative) directory.  I'm not throwing an exception
		// here because Laravel's view exception handler doesn't display the message.
		if (Str::is('/uploads/*', $this->value)) return $this->value;
		if (!Str::is('/img/*', $this->value)) return 'All Element images must be stored in the public/img directory';
		
		// Check if the image already exists in the uploads directory
		$uploads = File::publicPath(Config::get('decoy::core.upload_dir'));
		$dst = str_replace('/img/', $uploads.'/fragments/', $this->value);
		$dst_full_path = public_path().$dst;
		if (file_exists($dst_full_path)) return $dst;
		
		// Copy it to the uploads dir
		$dir = dirname($dst_full_path);
		if (!file_exists($dir)) mkdir($dir, 0775, true);
		copy(public_path().$this->value, $dst_full_path);

		// Return the new, non-full- path
		return $dst;
	}

	/**
	 * Make the input name for the admin index editor.  Periods are converted
	 * to | because the period isn't allowed in input names in PHP.
	 * See: http://stackoverflow.com/a/68742/59160
	 *
	 * @return string
	 */
	public function inputName() {
		return str_replace('.', '|', $this->key);
	}

	/**
	 * Prevent locale group from being set by overriding the method and making it
	 * a no-op
	 *
	 * @return void 
	 */
	public function setLocaleGroup() { }

	/**
	 * Render the element in a view
	 *
	 * @return string 
	 */
	public function __toString() {
		return $this->value();

	}

}