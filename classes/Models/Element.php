<?php

namespace Bkwld\Decoy\Models;

// Dependencies
use Bkwld\Decoy\Models\Traits\Encodable;
use Bkwld\Decoy\Models\Traits\HasImages;
use Bkwld\Library\Utils\File;
use Config;
use DB;
use Decoy;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Represents an indivudal Element instance, hydrated with the merge of
 * YAML and DB Element sources
 */
class Element extends Base
{
    use Encodable, HasImages {
        img as parentImg;
    }

    /**
     * Enable encoding
     *
     * @var array
     */
    private $encodable_attributes = ['value'];

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'key';

    /**
     * Indicates if the IDs are NOT auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * No timestamps necessary
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Register events
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        // Set the locale automatically using the locale key in the URL or the
        // default.
        static::creating(function (Element $el) {
            $el->setAttribute('locale', request()->segment(3) ?: Decoy::defaultLocale());
        });

        // A lot of extra stuff gets added to attributes with how I am hydrating
        // models with stuff from the YAML.  So, before, save, remove extra
        // attributes.
        static::saving(function (Element $el) {
            $el->setRawAttributes(array_only($el->getAttributes(), [
                'key', 'type', 'value', 'locale',
            ]));
        });
    }

    /**
     * Enforce the composite key while saving. Element has a composite primary
     * key accross `key` and `locale`
     * https://github.com/laravel/framework/issues/5355
     *
     * @param  Illuminate\Database\Eloquent\Builder $query
     * @return Illuminate\Database\Eloquent\Builder
     */
    protected function setKeysForSaveQuery(Builder $query)
    {
        parent::setKeysForSaveQuery($query);
        $query->where('locale', '=', $this->locale);

        return $query;
    }

    /**
     * Subclass setAttribute so that we can automatically set validation
     * rules based on the Element type
     *
     * @param  string $key
     * @param  mixed  $value
     * @return void
     */
    public function setAttribute($key, $value)
    {
        if ($key == 'type') {
            switch ($value) {
            case 'image': self::$rules['value'] = 'image';
            break;
            case 'file': self::$rules['value'] = 'file';
            break;
            case 'video-encoder': self::$rules['value'] = 'video';
            break;
        }
        }

        // Continue
        return parent::setAttribute($key, $value);
    }

    /**
     * Format the value before returning it based on the type
     *
     * @return mixed Stringable
     */
    public function value()
    {

        // Must return a string
        if (empty($this->value)) {
            return '';
        }

        // Different outputs depending on type
        switch ($this->type) {
            case 'boolean': return !empty($this->value);
            case 'image': return $this->img()->url;
            case 'textarea': return nl2br($this->value);
            case 'wysiwyg': return Str::startsWith($this->value, '<') ? $this->value : "<p>{$this->value}</p>";
            case 'checkboxes': return explode(',', $this->value);
            case 'video-encoder': return $this->encoding('value')->tag;
            case 'model': return $this->relatedModel();
            default: return $this->value;
        }
    }

    /**
     * Get the model referenced in a "model" field
     *
     * @return Model
     */
    protected function relatedModel()
    {
        $yaml = app('decoy.elements')->getConfig();
        $model = array_get($yaml, $this->key.'.class')
            ?: array_get($yaml, $this->key.',model.class');

        return $model::find($this->value);
    }

    /**
     * Make the input name for the admin index editor.  Periods are converted
     * to | because the period isn't allowed in input names in PHP.
     * See: http://stackoverflow.com/a/68742/59160
     *
     * @return string
     */
    public function inputName()
    {
        return str_replace('.', '|', $this->key);
    }

    /**
     * Prevent locale group from being set by overriding the method and making it
     * a no-op
     *
     * @return void
     */
    public function setLocaleGroup()
    {
    }

    /**
     * Look for default iamges using a named key. It was a lot simpler in the
     * integeration with the Elements admin UI to store the input name in the
     * "name" attribute
     *
     * @return Image
     */
    public function img()
    {
        // Check for an existing Image relation
        if (($image = $this->parentImg($this->inputName()))
            && $image->exists) {

            // If the Image represents a default image, but doesn't match the current
            // item from the config, trash the current one and build the new default
            // image.
            if ($replacement = $this->getReplacementImage($image)) {
                return $replacement;
            }

            // Return the found image
            return $image;
        }

        // Else return a default image
        return $this->defaultImage();
    }

    /**
     * Check if the Image represents a default image but is out of date
     *
     * @param  Image      $image
     * @return Image|void
     */
    protected function getReplacementImage(Image $image)
    {
        // Check that the image is in the elements dir, which means it's
        // a default image
        if (!strpos($image->file, '/elements/')) {
            return;
        }

        // Get the current file value form the YAML.  Need to check for the
        // shorthand with the type suffix as well.
        $yaml = app('decoy.elements')->assocConfig();
        $replacement = $yaml[$this->key]['value'];

        // Check if the filenames are the same
        if (pathinfo($image->file, PATHINFO_BASENAME)
            == pathinfo($replacement, PATHINFO_BASENAME)) {
            return;
        }

        // Since the filenames are not the same, remove the old image and generate
        // a new one
        $image->delete();
        $this->exists = true; // It actually does exist if there was an Image
        $this->value = $replacement;

        return $this->defaultImage();
    }

    /**
     * Check if the value looks like an image.  If it does, copy it to the uploads
     * dir so Croppa can work on it and return the modified path
     *
     * @return Image
     */
    public function defaultImage()
    {
        // Return an empty Image object if no default value
        if (empty($this->value)) {
            return new Image;
        }

        // All src images must live in the /img (relative) directory
        if (!Str::is('/img/*', $this->value)) {
            $msg = 'Element images must be stored in public/img: '.$this->value;
            throw new Exception($msg);
        }

        // Check if the image already exists in the uploads directory
        $src = $this->value;
        $src_abs = public_path($src);
        $path = str_replace('/img/', '/elements/', $src);
        if (!app('upchuck.disk')->has($path)) {

            // Copy it to the disk
            $stream = fopen($src_abs, 'r+');
            app('upchuck.disk')->writeStream($path, $stream);
            fclose($stream);
        }

        // Wrap the touching of the element and images table in a transaction
        // so that the element isn't updated if the images write fails
        DB::beginTransaction();

        // Update or create this Element instance
        $this->value = app('upchuck')->url($path);
        $this->save();

        // Create and return new Image instance. The Image::populateFileMeta()
        // requires an UploadedFile, so we need to do it manually here.
        $size = getimagesize($src_abs);
        $image = new Image([
            'file' => $this->value,
            'name' => $this->inputName(),
            'file_type' => pathinfo($src_abs, PATHINFO_EXTENSION),
            'file_size' => filesize($src_abs),
            'width'     => $size[0],
            'height'    => $size[1],
        ]);
        $this->images()->save($image);
        DB::commit();

        // Clear cached image relations
        unset($this->relations['images']);

        // Return the image
        return $image;
    }

    /**
     * Forward crop calls to the img instance, for simpler referencing
     *
     * @param  args...
     * @return Image
     */
    public function crop()
    {
        return call_user_func_array([$this->img(), 'crop'], func_get_args());
    }

    /**
     * Don't log changes. To do this right, I should aggregate a bunch of
     * Element changes into a single log.
     *
     * @param  string $action
     * @return boolean
     */
    public function shouldLogChange($action)
    {
        return false;
    }

    /**
     * Render the element in a view
     *
     * @return string
     */
    public function __toString()
    {
        $value = $this->value();
        if (is_array($value)) {
            return implode(',', $value);
        }

        return (string) $value;
    }
}
