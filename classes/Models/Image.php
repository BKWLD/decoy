<?php namespace Bkwld\Decoy\Models;

// Deps
use Croppa;
use Bkwld\Decoy\Markup\ImageElement;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Polymorphic one to many class that stores images for any model.
 */
class Image extends Base {

	/**
	 * JSON serialization
	 *
	 * @var array
	 */
	protected $visible = [
		'xs', 'xs2x',
		's', 's2x',
		'm', 'm2x',
		'l', 'l2x',
		'xl', 'xl2x',
		'bkgd_pos',
		'title',
	];
	protected $appends = [
		'xs', 'xs2x',
		's', 's2x',
		'm', 'm2x',
		'l', 'l2x',
		'xl', 'xl2x',
		'bkgd_pos',
	];

	/**
	 * The attributes that should be cast to native types.
	 *
	 * @var array
	 */
	protected $casts = [
		'file_size'   => 'integer',
		'width'       => 'integer',
		'height'      => 'integer',
		'crop_box'    => 'object',
		'focal_point' => 'object',
	];

	/**
	 * Validation rules
	 *
	 * @return array
	 */
	public static $rules = [
		'file' => 'image',
	];

	/**
	 * Uploadable attributes
	 *
	 * @var array
	 */
	protected $upload_attributes = ['file'];

	/**
	 * Stores config from chained transformations while a url or tag is generated
	 *
	 * @var array
	 */
	private $config = [];

	/**
	 * Register events
	 *
	 * @return void
	 */
	protected static function boot() {
		parent::boot();

		// Convert input strings to objects for casted attributes
		static::saving(function(Image $image) {
			$image->convertCastedJson();
		});

		// Need to process file meta before Upchuck converts the UploadFile object
		// to a URL string.  If the image file attribute has been set to empty,
		// stop the save and immediately delete.
		static::saving(function(Image $image) {
			if ($image->deletedBecauseEmpty()) return false;
			$image->populateFileMeta();
		}, config('upchuck.priority', 0) + 1);

		// If the image is deleted, delete Croppa crops
		static::updating(function(Image $image) {
			if ($image->isDirty('file')) $image->deleteCrops();
		});
		static::deleted(function(Image $image) {
			$image->deleteCrops();
		});
	}

	/**
	 * Polymorphic relationship
	 */
	public function imageable() { return $this->morphTo(); }

	/**
	 * Convert strings that may have been `fill()`ed but need to be objects to
	 * work with Laravel casting
	 *
	 * @return void
	 */
	public function convertCastedJson() {
		foreach($this->casts as $attribute => $cast) {
			if ($cast != 'object') continue;
			if (($val = $this->getAttributeValue($attribute))
				&& is_string($val)) {
					$this->setAttribute($attribute, json_decode($val));
			}
		}
	}

	/**
	 * If the file attribtue is empty, this Image has been marked for deletion.
	 * Return true to signal the image was deleted
	 *
	 * @return bool
	 */
	public function deletedBecauseEmpty() {
		if ($file = $this->getAttributeValue('file')) return false;
		if ($this->exists) $this->delete();
		return true;
	}

	/**
	 * Store file meta info in the database if a new File object is present
	 *
	 * @return void
	 */
	public function populateFileMeta() {
		$file = $this->getAttributeValue('file');
		if (!is_a($file,  UploadedFile::class)) return;
		$size = getimagesize($file->getPathname());
		$this->fill([
			'file_type' => $this->guessFileType($file),
			'file_size' => $file->getSize(),
			'width'     => $size[0],
			'height'    => $size[1],
		]);
	}

	/**
	 * Delete the crops that Croppa has made for the image
	 *
	 * @return void
	 */
	public function deleteCrops() {

		// Get at the file path using "original" so this function can be called as
		// part of an "updating" callback
		$file = $this->getOriginal('file');

		// Tell Croppa to delete the crops.  The actual file will be deleted by
		// Upchuck automatically.
		Croppa::reset($file);
	}

	/**
	 * Get file type
	 *
	 * @param UploadedFile
	 * @return string
	 */
	protected function guessFileType(UploadedFile $file) {
		$type = $file->guessClientExtension();
		switch($type) {
			case 'jpeg': return 'jpg';
			default: return $type;
		}
	}

	/**
	 * Set the crop dimenions
	 *
	 * @param  integer $width
	 * @param  integer $height
	 * @param  array   $options Croppa options array
	 * @return $this
	 */
	public function crop($width = null, $height = null, $options = null) {
		$this->config = [
			'width'   => $width,
			'height'  => $height,
			'options' => $options,
		];
		return $this;
	}

	/**
	 * Get the config, merging defaults in so that all keys in the array are
	 * present.  This also applies the crop choices from the DB.
	 *
	 * @return array
	 */
	public function getConfig() {

		// Create default keys for the config
		$config = array_merge([
			'width'   => null,
			'height'  => null,
			'options' => null,
		], $this->config);

		// Add crops
		if ($crop = $this->getAttributeValue('crop_box')) {
			if (!is_array($config['options'])) $config['options'] = [];
			$config['options']['trim_perc'] = [
				round($crop->x1, 4),
				round($crop->y1, 4),
				round($crop->x2, 4),
				round($crop->y2, 4),
			];
		}

		// Return config
		return $config;
	}

	/**
	 * Output the image URL with any queued Croppa transformations.  Note, it's
	 * possible that "file" is empty, in which case this returns an empty string.
	 * This clears the stored config on every call.
	 *
	 * @return string
	 */
	public function getUrlAttribute() {

		// Figure out the URL
		$url = $this->urlify(1);

		// Clear the instance config so that subsequent calls don't inherit anything
		$this->config = [];

		// Return the url
		return $url;
	}

	/**
	 * Output image for background style
	 *
	 * @return string
	 */
	public function getBkgdAttribute() {
		return sprintf('background-image: url(\'%s\');', $this->getUrlAttribute())
			.$this->getBackgroundPositionAttribute();
	}

	/**
	 * Output an image tag.  The ?: was necessary because HtmlObject sets NULL
	 * values to "true" in the rendered attribute.
	 *
	 * @return Element
	 */
	public function getTagAttribute() {
		return ImageElement::img()
			->isSelfClosing()
			->src($this->getUrlAttribute() ?: false)
			->alt($this->getAttribute('title') ?: false);
	}

	/**
	 * Output a div tag.
	 * https://www.w3.org/TR/wai-aria/roles#img
	 *
	 * @return Element
	 */
	public function getDivAttribute() {
		return ImageElement::div()
			->style($this->getBkgdAttribute())
			->role('img')
			->ariaLabel($this->getAltAttribute());
	}

	/**
	 * Convert the focal_point attribute to a CSS background-position.
	 *
	 * @return string
	 */
	public function getBackgroundPositionAttribute() {
		if (!$value = $this->getBackgroundPositionAttribute()) return;
		return sprintf('background-position: %s;', $value);
	}

	/**
	 * Convert the focal point to the VALUE portion of the CSS
	 * background-position.  This is also used in the serialization conversion
	 * and is named to be friendly to that format.
	 *
	 * @return string
	 */
	public function getBkgdPosAttribute() {
		if (!$point = $this->getAttributeValue('focal_point')) return;
		return sprintf('%s%% %s%%', $point->x*100, $point->y*100);
	}

	/**
	 * Convenience accessor for the title attribute
	 *
	 * @return string
	 */
	public function getAltAttribute() {
		return $this->getAttributeValue('title');
	}

	/**
	 * Accessors for different sizes.  They are calculated has percentages of
	 * 1366, which we take to be the 1x "desktop" resolution.  1366 is currently
	 * the most popular desktop resolution.
	 *
	 * @return string
	 */
	public function getXsAttribute()   { return $this->urlify(420/1366); }
	public function getXs2xAttribute() { return $this->urlify(840/1366); }
	public function getSAttribute()    { return $this->urlify(768/1366); }
	public function getS2xAttribute()  { return $this->urlify(1536/1366); }
	public function getMAttribute()    { return $this->urlify(1024/1366); }
	public function getM2xAttribute()  { return $this->urlify(2048/1366); }
	public function getLAttribute()    { return $this->urlify(1366/1366); }
	public function getL2xAttribute()  { return $this->urlify(2732/1366); }
	public function getXlAttribute()   { return $this->urlify(1920/1366); }
	public function getXl2xAttribute() { return $this->urlify(3840/1366); }

	/**
	 * Make paths full URLs so these can be used directly in APIs or for Open
	 * Graph tags, for example.
	 *
	 * @param  float $scale How much to scale
	 * @return string url
	 */
	public function urlify($scale = 1) {
		$config = $this->getConfig();
		$path = Croppa::url($this->getAttributeValue('file'),
			$config['width'] * $scale,
			$config['height'] * $scale,
			$config['options']
		);
		if ($path) return asset($path);
	}

}
