<?php

namespace Bkwld\Decoy\Observers;

// Deps
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
     * @param  string $event
     * @param  array $payload Contains:
     *    - Bkwld\Decoy\Models\Base
     *    - Illuminate\Validation\Validator
     * @return void
     */
    public function onValidating($event, $payload)
    {
        // Destructure payload
        list($model, $validation) = $payload;

        // Only act on locally hosted files
        if (config('upchuck.disk.driver') != 'local') {
            return;
        }

        // Get all the file related rules
        // https://regex101.com/r/oP4kD2/1
        $rules = array_filter($validation->getRules(), function ($rules) {
            foreach ($rules as $rule) {
                if (preg_match('#^(image|file|video|mimes|dimensions)#', $rule)) {
                    return true;
                }
            }
        });

        // For each of the file rules, if the input has a value, make a file
        // instance for it if it's a local path.
        $data = $validation->getData();
        foreach (array_keys($rules) as $attribute) {

            // Skip if the attribute isn't in the input.  It may not be for
            // images or other nested models.  Their validation should get
            // triggered later through NestedModels behavior.
            if (!array_key_exists($attribute, $data)) {
                continue;

            // Skip if a file was uploaded for this attribtue
            } else if (is_a($data[$attribute], File::class)) {
                continue;

            // If the value is empty, because the user is deleting the file
            // instance, set it to an empty string instead of the default
            // (null).  Null requires `nullable` validation rules to be set
            // and I don't want to require that.
            } else if (empty($data[$attribute])) {
                $data[$attribute] = '';

            // Create the file instance and clear the data instance
            } else {
                $data[$attribute] = $this->makeFileFromPath($data[$attribute]);
            }
        }

        // Replace the files and data with the updated set. `setData()` expects
        // the data to contain files in it.  But `getData()` strips out the
        // files.  Thus, they need to be merged back in before being set.
        $validation->setData(array_merge($data));
    }

    /**
     * Make a file instance using uphuck from the string input value
     *
     * @param string $path
     * @return File
     */
    public function makeFileFromPath($path)
    {
        $upchuck_path = app('upchuck')->path($path);
        $absolute_path = config('upchuck.disk.path').'/'.$upchuck_path;
        return new File($absolute_path);
    }

}
