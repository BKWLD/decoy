<?php namespace App;
use Bkwld\Decoy\Models\Base;
use Bkwld\Decoy\Models\Traits\HasImages;

class Tag extends Base {
    use HasImages;

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
        return $this->morphedByMany('App\Article', 'taggable');
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

}
