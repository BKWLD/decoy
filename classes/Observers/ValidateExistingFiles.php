<?php

namespace Bkwld\Decoy\Observers;

// Deps
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

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
            $value = array_get($data, $attribute);

            // Skip if the attribute isn't in the input.  It may not be for
            // images or other nested models.  Their validation should get
            // triggered later through NestedModels behavior.
            if (!array_has($data, $attribute)) {
                continue;

            // Skip if a file was uploaded for this attribtue
            } else if (is_a($value, File::class)) {
                continue;

            // If the value is empty, because the user is deleting the file
            // instance, set it to an empty string instead of the default
            // (null).  Null requires `nullable` validation rules to be set
            // and I don't want to require that.
            } else if (!$value) {
                array_set($data, $attribute, '');

            // Create the file instance and clear the data instance
            } else {
                array_set($data, $attribute, $this->makeFileFromPath($value));
            }
        }

        // Replace the files and data with the updated set. `setData()` expects
        // the data to contain files in it.  But `getData()` strips out the
        // files.  Thus, they need to be merged back in before being set.
        $validation->setData(array_merge($data));
    }

    /**
     * Make an UploadedFile instance using Upchuck from the string input value
     *
     * @param string $path
     * @return UploadedFile
     */
    public function makeFileFromPath($path)
    {
        $upchuck_path = app('upchuck')->path($path);
        $absolute_path = config('upchuck.disk.path').'/'.$upchuck_path;
        return new UploadedFile(
            $absolute_path, basename($absolute_path),
            null, null, // Default mime and error
            true); // Enable test mode so local file will be pass as uploaded
    }

}
