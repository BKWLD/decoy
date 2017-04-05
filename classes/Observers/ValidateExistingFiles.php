<?php

namespace Bkwld\Decoy\Observers;

use Config;
use Symfony\Component\HttpFoundation\File\File;

/**
 * When a form is updated (as opposed to created) the previous files are
 * strings and their mime validations would fail.  This creates file instances
 * for them that can be validated
 */
class ValidateExistingFiles
{
    /**
     * Massage validation handling
     *
     * @param  Bkwld\Decoy\Models\Base         $model
     * @param  Illuminate\Validation\Validator $validation
     * @return void
     */
    public function onValidating($model, $validation)
    {
        // Only act on locally hosted files
        if (Config::get('upchuck.disk.driver') != 'local') {
            return;
        }

        // Get all the file related rules
        // https://regex101.com/r/oP4kD2/1
        $rules = array_filter($validation->getRules(), function ($rules) {
            foreach ($rules as $rule) {
                if (preg_match('#^(image|file|video|mimes)#', $rule)) {
                    return true;
                }
            }
        });

        // For each of the file rules, if the input has a value, make a file
        // instance for it if it's a local path.
        $data = $validation->getData();

        foreach ($rules as $attribute => $rules) {

            // Skip if a file was uploaded for this attribtue or if the existing data
            // is undefined
            if (empty($data[$attribute]) || is_a($data[$attribute], File::class)) {
                continue;
            }

            // Create the file instance and clear the data instance
            $data[$attribute] = new File(Config::get('upchuck.disk.path').'/'.app('upchuck')->path($data[$attribute]));
        }

        // Replace the files and data with the updated set. `setData()` expects the
        // data to contain files in it.  But `getData()` strips out the files.  Thus,
        // they need to be merged back in before being set.
        $validation->setData(array_merge($data));
    }
}
