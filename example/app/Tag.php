<?php namespace App;

use Bkwld\Decoy\Models\Base;
use Bkwld\Decoy\Models\Traits\HasImages;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tag extends Base
{
    use HasImages, SoftDeletes;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'name' => 'required',
    ];

    /**
     * List of all relationships
     *
     * @return Illuminate\Database\Eloquent\Relations\Relation
     */
    public function articles()
    {
        return $this->morphedByMany(\App\Article::class, 'taggable');
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
        $query->orderBy('name');
    }

    /**
     * A no-op that should return the URI (an absolute path or a fulL URL) to the record
     *
     * @return string
     */
    public function getUriAttribute() {
        return route('tag', $this->id);
    }
}
