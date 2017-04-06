<?php namespace App;

use Bkwld\Decoy\Models\Base;
use Bkwld\Decoy\Models\Traits\HasImages;

class Article extends Base
{
    use HasImages;

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'title' => 'required',
        'slug' => 'alpha_dash|unique:articles',
        'images.default' => 'image',
        'date' => 'required',
    ];

    public static $categories = [
        'first' => 'first',
        'second' => 'second',
        'third' => 'third',
    ];

    protected $visible = [ 'slides' ];

    /**
     * List of all relationships
     *
     * @return Illuminate\Database\Eloquent\Relations\Relation
     */
    public function tags()
    {
        return $this->morphToMany(\App\Tag::class, 'taggable');
    }

    public function slides()
    {
        return $this->hasMany(\App\Slide::class);
    }

    /**
     * Put new instances at the end
     *
     * @return void
     */
    public function onCreating()
    {
        if (isset($this->position)) {
            return;
        }
        $this->position = self::max('position') + 1;
    }

    /**
     * Orders instances of this model in the admin as well as default ordering
     * to be used by public site implementation.
     *
     * @param  Illuminate\Database\Query\Builder $query
     * @return void
     */
    public function scopeOrdered($query)
    {
        $query->positioned();
    }

    /**
     * Return the URI to instances of this model
     *
     * @return string A URI that the browser can resolve
     */
    public function getUriAttribute()
    {
        return route('article', $this->slug);
    }

    /**
     * Render markup for the "featured" column in the admin listing
     *
     * @return string HTML
     */
    public function getAdminFeaturedAttribute()
    {
        return $this->featured ? '<span class="badge">Featured</span>' : '';
    }
}
