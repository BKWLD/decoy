<?php

namespace Bkwld\Decoy\Models\Traits;

// Deps
use Bkwld\Library\Utils\Text as TextUtils;

/**
 * Adds behavior for making the model exportable to CSV and potentially other
 * formats
 */
trait Exportable
{
    /**
     * Return whether the model is exportable
     *
     * @return boolean
     */
    public function isExportable()
    {
        return $this->exportable;
    }

    /**
     * No-op, used to configure the query used to fetch exportable records
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return void
     */
    public function scopeExporting($query) { }

    /**
     * Convert all array values to a comma-delimited string
     *
     * @return array
     */
    public function forExport()
    {
        // Make a clone because converting to array directly was failing
        $clone = tap(new static, function ($instance) {
            $instance->setRawAttributes($this->getAttributes());
            $instance->setRelations($this->relations);
        });

        // Convert clone to array
        $attributes = $clone->toArray();

        // Massage the values
        return collect($attributes)->map(function($value, $key) {
            return $this->mapExportAttribute($value, $key);
        })->toArray();
    }

    /**
     * Massage attribute values for export
     *
     * @return scalar
     */
    protected function mapExportAttribute($value, $key)
    {
        // If an images relationship
        if ($key == 'images') {
            return implode(',', array_map(function($image) {
                return $image['file'];
            }, $value));
        }

        // If another array...
        if (is_array($value)) {
            return implode(',', array_map(function($child) {

                // If sub array, like if this was some relation, return the id
                // so this becomes an array of those ids
                if (is_array($child) && isset($child['id'])) {
                    return $child['id'];
                }

                // If anything else, just return it
                return $child;
            }, $value));
        }

        // Otherwise, just pass through value
        return $value;
    }

    /**
     * Make the header the CSV header row
     *
     * @return array
     */
    public function makeCsvHeaderNames()
    {
        $headers = array_keys($this->forExport());
        return array_map(function($key) {
            switch($key) {
                case 'id':
                case 'url': return strtoupper($key);
                default: return TextUtils::titleFromKey($key);
            }
        }, $headers);
    }
}
