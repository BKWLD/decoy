<?php

namespace Bkwld\Decoy\Observers;

/**
 * Trigger encoding or delete the encodings rows
 */
class Encoding
{
    /**
     * Start a new encode if a new encodable file was uploaded
     *
     * @param  string $event
     * @param  array $payload Contains:
     *    - Bkwld\Decoy\Models\Base $model
     * @return void
     */
    public function onSaving($event, $payload)
    {
        list($model) = $payload;

        if (!$this->isEncodable($model)) {
            return;
        }

        foreach ($model->getDirtyEncodableAttributes() as $attribute) {

            // If the attribute has a value, encode the attribute
            if ($model->getAttribute($attribute)) {
                $model->encodeOnSave($attribute);
            }

            // Otherwise delete encoding references
            elseif ($encoding = $model->encoding($attribute)) {
                $encoding->delete();
            }
        }
    }

    /**
     * Delete all encodes on the model
     *
     * @param  string $event
     * @param  Bkwld\Decoy\Models\Base $model
     * @return void
     */
    public function onDeleted($event, $model)
    {
        if (!$this->isEncodable($model)) {
            return;
        }

        $model->deleteEncodings();
    }

    /**
     * Check if a model should be encoded
     *
     * @param  Bkwld\Decoy\Models\Base $model
     * @return boolean
     */
    public function isEncodable($model)
    {
        if (!method_exists($model, 'getDirtyEncodableAttributes')) {
            return false;
        }

        if (is_a($model, 'Bkwld\Decoy\Models\Element') && $model->getAttribute('type') != 'video-encoder') {
            return false;
        }

        return true;
    }
}
