<?php

namespace Bkwld\Decoy\Observers;

use Bkwld\Decoy\Models\Element;
use Config;
use Illuminate\Support\Str;

/**
 * Generate a locale_group attribute for localized models if
 * one doesn't already exist.
 */
class Localize
{
    /**
     * Called on model saving
     *
     * @param Bkwld\Decoy\Models\Base $model
     */
    public function handle($model)
    {
        if (!empty($model->locale)
            && empty($model->locale_group)
            && !is_a($model, Element::class) // Elements don't have groups
            && ($locales = Config::get('decoy.site.locales'))
            && count($locales) > 1) {
            $model->setAttribute('locale_group', Str::random());
        }
    }
}
