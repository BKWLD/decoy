<?php

namespace Bkwld\Decoy\Models\Traits;

// Deps
use Bkwld\Decoy\Models\Admin;
use Bkwld\Decoy\Models\Change;
use Bkwld\Decoy\Models\Image;
use Illuminate\Database\Eloquent\Builder;

/**
 * Enable logging changes to models
 */
trait Loggable
{
    /**
     * The name of the scope that is applied to make trashed versions to be
     * viewable
     *
     * @var string
     */
    static public $LOGGABLE_SCOPE = 'view trashed versions';

    /**
     * Previous (to the change referenced in the request query) changes made
     * to this model
     *
     * @var Collection
     */
    private $previous_changes;

    /**
     * Get the polymorphic relationship to Changes
     *
     * @return Illuminate\Database\Eloquent\Relations\Relation
     */
    public function changes()
    {
        return $this->morphMany(Change::class, 'loggable', 'model', 'key');
    }

     /**
     * Boot events
     *
     * @return void
     */
    public static function bootLoggable()
    {
        // Automatically eager load the images relationship
        static::addGlobalScope(static::$LOGGABLE_SCOPE, function (Builder $builder) {
            static::showTrashedVersion($builder);
        });

        // Substitute attribute values from changes on load
        static::retrieved(function($model) {
            $model->replaceAttributesWithChange();
        });
    }

    /**
     * Show trashed models for matching change
     *
     * @param  Builder $builder
     * @return void
     */
    private static function showTrashedVersion(Builder $builder)
    {
        if (($change = static::lookupRequestedChange())
            && static::builderMatchesChange($change, $builder)) {
            static::includeTrashed($change, $builder);
        }
    }

    /**
     * Get a Change record mentioned in the query, if appropriate
     *
     * @return Change|void
     */
    private static function lookupRequestedChange()
    {
        // Only run if the query param is present
        if (!$change_id = request(Change::QUERY_KEY)) {
            return;
        }

        // Don't execute for classes that result in recusirve queries when the
        // Change model gets built below
        $class = get_called_class();
        if (in_array($class, [Change::class, Admin::class, Image::class])) {
            return;
        }

        // Check whether the referenced Change is for this class
        $change = Change::find($change_id);
        if ($class == $change->model) {
            return $change;
        }
    }

    /**
     * Does the Change referenced in the GET query match the conditions already
     * applied in the query builder?
     *
     * @param  Change $change
     * @param  Builder $builder
     * @return boolean
     */
    private static function builderMatchesChange(Change $change, Builder $builder)
    {
        $class = $change->model;
        $route_key_name = (new $class)->getRouteKeyName();
        return collect($builder->getQuery()->wheres)
            ->contains(function($where) use ($change, $route_key_name) {

                // If the builder is keyed to a simple "id" in the route, return
                // whether the Change matches it.
                if ($route_key_name == 'id') {
                    return $where['column'] == $route_key_name
                        && $where['operator'] == '='
                        && $where['value'] == $change->key;

                // Otherwise compare against model logged by the change. The
                // scope needs to be removed to prevent recursion.
                } else {
                    $value = $change->changedModel()
                        ->withoutGlobalScope(static::$LOGGABLE_SCOPE)
                        ->first()
                        ->$route_key_name;
                    return $where['column'] == $route_key_name
                        && $where['operator'] == '='
                        && $where['value'] == $value;
                }
        });
    }

    /**
     * Manually remove already added soft deleted where conditions.  This is
     * necessary since withTrashed() can't be called on a Builder.
     * http://yo.bkwld.com/1K04151B2h3M
     *
     * @param  Change $change
     * @param  Builder $builder
     * @return void
     */
    private static function includeTrashed(Change $change, Builder $builder)
    {
        $class = $change->model;
        $table = (new $class)->getTable();
        foreach($builder->getQuery()->wheres as $key => $where) {
            if ($where['column'] == $table.'.deleted_at'
                && $where['type'] == 'Null') {
                unset($builder->getQuery()->wheres[$key]);
                break;
            }
        }
    }

    /**
     * Replace all the attributes with those from the specified Change specified
     * in the reqeust query.
     *
     * @return void
     */
    private function replaceAttributesWithChange()
    {
        if (($change = static::lookupRequestedChange())
            && $change->key == $this->getKey()) {
            $this->attributes = array_merge(
                $this->attributes,
                $this->attributesAtChange($change)
            );
        }
    }

    /**
     * Get the attributes of the model at a given Change
     *
     * @param  Change $change
     * @return array
     */
    private function attributesAtChange(Change $change)
    {
        return $this->previousChanges($change)
            ->reduce(function($attributes, $change) {
                return array_merge($attributes, $change->changed);
            }, []);
    }

    /**
     * Get the list of pervious changes of this model, storing it to reduce
     * future lookups
     *
     * @param  Change $change
     * @return Collection
     */
    private function previousChanges(Change $change)
    {
        return $this->changes()
            ->where('changes.id', '<=', $change->id)
            ->orderBy('changes.id', 'asc')
            ->get();

    }

}
