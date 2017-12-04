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
        return $this->mapExportAttributes($this->cloneForExport());
    }

    /**
     * Clone this model for export because converting self to an array directly
     * was causing errors.  This code is based on the Laravel replicate()
     *
     * @return $this
     */
    protected function cloneForExport()
    {
        return tap(new static, function ($instance) {
            $instance->setRawAttributes($this->getAttributes());
            $instance->setRelations($this->relations);
            $instance->setAppends($this->appends);
            $instance->setVisible($this->visible);
            $instance->setHidden($this->hidden);
        });
    }

    /**
     * Massage attribute values. The CSV needs a flat array.
     *
     * @return array
     */
    protected function mapExportAttributes($attributes)
    {
        return collect($attributes->toArray())->map(function($value, $key) {
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

                // id must be lowercase for opening in excel
                // https://annalear.ca/2010/06/10/why-excel-thinks-your-csv-is-a-sylk/
                case 'id': return $key;

                // Make common acronyms upper case
                case 'uid':
                case 'pid':
                case 'guid':
                case 'cta':
                case 'url': return strtoupper($key);

                // Default to title casing fields
                default: return TextUtils::titleFromKey($key);
            }
        }, $headers);
    }
}
