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

    /**
     * Example options for radiolist
     *
     * @var array
     */
    public static $categories = [
        'news' => 'News Story',
        'blog' => 'Blog Post',
    ];

    /**
     * Example options for checklist
     *
     * @var array
     */
    public static $topics = [
        'cars' => 'Cars',
        'trucks' => 'Large trucks',
        'minitrucks' => 'Small trucks',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'topic' => 'array',
    ];

    /**
     * Tags relation
     *
     * @return Illuminate\Database\Eloquent\Relations\Relation
     */
    public function tags()
    {
        return $this->morphToMany(\App\Tag::class, 'taggable');
    }

    /**
     * Slides relation
     *
     * @return Illuminate\Database\Eloquent\Relations\Relation
     */
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
        if (empty($this->position)) {
            $this->position = self::max('position') + 1;
        }
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
