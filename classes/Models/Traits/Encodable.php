<?php

namespace Bkwld\Decoy\Models\Traits;

use Bkwld\Decoy\Models\Encoding;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;

/**
 * Mix this into models that join to the Encoding model to
 * add the Laravel relationship and add helper methods
 */
trait Encodable
{
    /**
     * Define a `private $encodable_attributes` property like:
     *
     *  private $encodable_attributes = [
     *      'video',
     *  ];
     */

     /**
     * Boot events
     *
     * @return void
     */
    public static function bootEncodable()
    {
        // Automatically eager load the images relationship
        static::addGlobalScope('encodings', function (Builder $builder) {
            $builder->with('encodings');
        });
    }

    /**
     * Polymorphic relationship definition
     *
     * @return Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function encodings()
    {
        return $this->morphMany('Bkwld\Decoy\Models\Encoding', 'encodable');
    }

    /**
     * Find the encoding for a given database field
     *
     * @param  string         $attribute
     * @return Encoding|false
     */
    public function encoding($attribute = 'video')
    {
        $encodings = $this->encodings;
        if (!is_a($encodings, Collection::class)) {
            $encodings = Encoding::hydrate($encodings);
        }

        return $encodings->first(function ($i, $encoding) use ($attribute) {
            return data_get($encoding, 'encodable_attribute') == $attribute;
        });
    }

    /**
     * Get all the attributes on a model who support video encodes and are dirty.
     * An encode is considered dirty if a file is uploaded, replaced, marked for
     * deletion OR if it's preset has changed.
     *
     * @return array
     */
    public function getDirtyEncodableAttributes()
    {
        if (empty($this->encodable_attributes)) {
            return [];
        }

        return array_filter($this->encodable_attributes, function ($attribute) {

            // The file has changed
            if ($this->isDirty($attribute)) {
                return true;
            }

            // The encoding preset is changing
            return $this->hasDirtyPreset($attribute);
        });
    }

    /**
     * Check if the preset choice is dirty
     *
     * @param  string  $attribute
     * @return boolean
     */
    public function hasDirtyPreset($attribute)
    {

        // Require a previous encoding instance
        return ($encoding = $this->encoding($attribute))

            // Make sure the input actually contains a preset.  It won't in cases like
            // the AJAX PUT during listing drag and drop sorting or visibility toggle
            && ($preset_key = $this->encodingPresetInputKey($attribute))
            && request()->exists($preset_key)

            // Check if the preset has changed
            && request($preset_key) != $encoding->preset;
    }

    /**
     * Get the value of an encoding preset given the encoding attribute
     *
     * @param  string $attribute
     * @return string
     */
    public function encodingPresetInputVal($attribute)
    {
        return request($this->encodingPresetInputKey($attribute));
    }

    /**
     * Make the encoding preset input key for an encodable attribute
     *
     * @param  string $attribute
     * @return string
     */
    public function encodingPresetInputKey($attribute)
    {
        $key = is_a($this, 'Bkwld\Decoy\Models\Element')
            ? $this->inputName() : $attribute;

        return '_preset.'.$key;
    }

    /**
     * A utitliy function to create status badges for Decoy listings
     *
     * @return string HTML
     */
    public function adminColEncodeStatus()
    {
        if (!$encode = $this->encoding()) {
            return '<span class="label">Pending</span>';
        }
        switch ($encode->status) {
            case 'pending':
                return '<span class="label">'.ucfirst($encode->status).'</span>';

            case 'error':
            case 'cancelled':
                return '<span class="label label-important">'.ucfirst($encode->status).'</span>';

            case 'queued':
            case 'processing':
                return '<span class="label label-info">'.ucfirst($encode->status).'</span>';

            case 'complete':
                return '<span class="label label-success">'.ucfirst($encode->status).'</span>';
        }
    }

    /**
     * Create an encoding instance which, in affect, begins an encode.  This
     * should be invoked before the model is saved.  For instance, from saving()
     * handler
     *
     * @param  string $attribute The name of the attribtue on the model that
     *                           contains the source for the encode
     * @return void
     */
    public function encodeOnSave($attribute)
    {
        // Preserve the key for the saved callback.  It's a mystery to me why, but
        // when Elements are being saved, the key would become '0' between here
        // and the `saved()` callback.
        $key = $this->getKey();

        // Create a new encoding model instance. It's callbacks will talk to the
        // encoding provider. Save it after the model is fully saved so the foreign
        // id is available for the  polymorphic relationship.
        $this->saved(function ($model) use ($attribute, $key) {

            // Make sure that that the model instance handling the event is the one
            // we're updating.
            if ($this != $model) {
                return;
            }

            // Restore the key value (see above).  It will be defined for Elements but
            // not of most models.
            if ($key) {
                $model->setAttribute($this->getKeyName(), $key);
            }

            // Create the new encoding
            $this->encode($attribute, $this->encodingPresetInputVal($attribute));
        });
    }

    /**
     * Delete any existing encoding for the attribute and then encode from the
     * source.  The deleting happens automatically onCreating.
     *
     * @param  string   $attribute The attribute on the model to use as source
     * @param  string   $preset    The output config key
     * @return Encoding The new output instance
     */
    public function encode($attribute, $preset)
    {
        $encoding = new Encoding([
            'encodable_attribute' => $attribute,
            'preset' => $preset,
        ]);

        $this->encodings()->save($encoding);

        return $encoding;
    }

    /**
     * Delete all the encodings individually so model callbacks can respond
     *
     * @return void
     */
    public function deleteEncodings()
    {
        $this->encodings()->get()->each(function ($encode) {
            $encode->delete();
        });
    }
}
