<?php

namespace Bkwld\Decoy\Controllers;

use App;
use URL;
use View;
use Decoy;
use Config;
use Former;
use Request;
use Redirect;
use Validator;
use Illuminate\Support\Str;
use Bkwld\Decoy\Models\Image;
use Bkwld\Library\Utils\File;
use Bkwld\Decoy\Models\Element;
use Bkwld\Decoy\Exceptions\ValidationFail;

/**
 * Render a form that allows admins to override language files
 */
class Elements extends Base
{
    /**
     * @var string
     */
    protected $title = 'Elements';

    /**
     * @var string
     */
    protected $description = 'Copy, images, and files that aren\'t managed as part of an item in a list.';

    /**
     * All elements view
     *
     * @param  string                   $locale The locale to load from the DB
     * @param  string                   $tab    A deep link to a specific tab.  Will get processed by JS
     * @return Illuminate\Http\Response
     */
    public function index($locale = null, $tab = null)
    {
        // If there are no locales, treat the first argument as the tab
        if (!($locales = Config::get('decoy.site.locales')) || empty($locales)) {
            $tab = $locale;
            $locale = null;

        // Otherwise, set a default locale if none was specified
        } elseif (!$locale) {
            $locale = Decoy::defaultLocale();
        }

        // Get all the elements for the current locale
        $elements = app('decoy.elements')->localize($locale)->hydrate(true);

        // If the user has customized permissions, filter the elements to only the
        // allowed pages of elements.
        if ($permissions = app('decoy.user')->getPermissionsAttribute()) {
            $elements->onlyPages($permissions->elements);
        }

        // If handling a deep link to a tab, verify that the passed tab
        // slug is a real key in the data.  Else 404.
        if ($tab && !in_array($tab, $elements->pluck('page_label')
            ->map(function ($title) {
                return Str::slug($title);
            })
            ->all())) {
            App::abort(404);
        }

        // Populate form
        Former::withRules($elements->rules());
        Former::populate($elements->populate());

        // Convert the collection to models for simpler manipulation
        $elements = $elements->asModels();

        // Set the breadcrumbs NOT include the locale/tab
        app('decoy.breadcrumbs')->set([
            route('decoy::elements') => $this->title,
        ]);

        // Render the view
        return $this->populateView('decoy::elements.index', [
            'elements' => $elements,
            'locale' => $locale,
            'tab' => $tab,
        ]);
    }

    /**
     * A helper function for rendering the list of fields
     *
     * @param  Bkwld\Decoy\Models\Element $el
     * @param  string                     $key
     * @return Former\Traits\Object
     */
    public static function renderField($el, $key = null)
    {
        if (!$key) {
            $key = $el->inputName();
        }

        $id = str_replace('|', '-', $key);

        switch ($el->type) {
            case 'text':
                return Former::text($key, $el->label)
                    ->blockHelp($el->help)
                    ->id($id);

            case 'textarea':
                return Former::textarea($key, $el->label)
                    ->blockHelp($el->help)
                    ->id($id);

            case 'wysiwyg':
                return Former::wysiwyg($key, $el->label)
                    ->blockHelp($el->help)
                    ->id($id);

            case 'image':
                return Former::image($key, $el->label)
                    ->forElement($el)
                    ->id($id);

            case 'file':
                return Former::upload($key, $el->label)
                    ->blockHelp($el->help)
                    ->id($id);

            case 'boolean':
                return Former::checkbox($key, false)
                    ->checkboxes([
                        "<b>{$el->label}</b>" => [
                            'name' => $key,
                            'value' => 1,
                        ],
                    ])
                    ->blockHelp($el->help)
                    ->id($id)
                    ->push();

            case 'select':
                return Former::select($key, $el->label)
                    ->options($el->options)
                    ->blockHelp($el->help)
                    ->id($id);

            case 'radios':
                return Former::radiolist($key, $el->label)
                    ->from($el->options)
                    ->blockHelp($el->help)
                    ->id($id);

            case 'checkboxes':
                return Former::checklist($key, $el->label)
                    ->from($el->options)
                    ->blockHelp($el->help)
                    ->id($id);

            case 'video-encoder':
                return Former::videoEncoder($key, $el->label)
                    ->blockHelp($el->help)
                    ->model($el)
                    ->preset($el->preset)
                    ->id($id);

            case 'model':
                return Former::belongsTo($key, $el->label)
                    ->parent($el->class)
                    ->blockHelp($el->help)
                    ->id($id);
        }
    }

    /**
     * Handle form post
     *
     * @param  string                   $locale The locale to assign to it
     * @return Illuminate\Http\Response
     */
    public function store($locale = null)
    {
        // Get the default locale
        if (!$locale) {
            $locale = Decoy::defaultLocale();
        }

        // Hydrate the elements collection
        $elements = app('decoy.elements')
            ->localize($locale)
            ->hydrate(true);

        // If the user has customized permissions, filter the elements to only the
        // allowed pages of elements.
        if ($permissions = app('decoy.user')->getPermissionsAttribute()) {
            $elements->onlyPages($permissions->elements);
        }

        // Get all the input such that empty file fields are removed from the input.
        $input = Decoy::filteredInput();

        // Validate the input
        $validator = Validator::make($input, $elements->rules());
        if ($validator->fails()) {
            throw new ValidationFail($validator);
        }

        // Merge the input into the elements and save them.  Key must be converted back
        // from the | delimited format necessitated by PHP
        $elements->asModels()->each(function (Element $el) use ($elements, $input) {

            // Inform the model as to whether the model already exists in the db.
            if ($el->exists = $elements->keyUpdated($el->key)) {
                $el->syncOriginal();
            }

            // Handle images differently, since they get saved in the Images table
            if ($el->type == 'image') {
                return $this->storeImage($el, $input);
            }

            // Empty file fields will have no key as a result of the above filtering
            $key = $el->inputName();
            if (!array_key_exists($key, $input)) {

                // If no new video was uploaded but the preset was changed, re-encode
                if ($el->type == 'video-encoder' && $el->hasDirtyPreset('value')) {
                    $el->encode('value', $el->encodingPresetInputVal('value'));
                }

                return;
            }
            $value = $input[$key];

            // If value is an array, like it would be for the "checkboxes" type, make
            // it a comma delimited string
            if (is_array($value)) {
                $value = implode(',', $value);
            }

            // We're removing the carriage returns because YAML won't include them and
            // all multiline YAML config values were incorrectly being returned as
            // dirty.
            $value = str_replace("\r", '', $value);

            // Check if the model is dirty, manually.  Laravel's performInsert()
            // doesn't do this, thus we must check ourselves.
            if ($value == $el->value) {
                return;
            }

            // If type is a video encoder and the value is empty, delete the row to
            // force the encoding row to also delete.  This is possible because
            // videos cannot have a YAML set default value.
            if (!$value && $el->type == 'video-encoder') {
                return $el->delete();
            }

            // Save it
            $el->value = Request::hasFile($key) ?
                app('upchuck.storage')->moveUpload(Request::file($key)) :
                $value;
            $el->save();
        });

        // Clear the cache
        $elements->clearCache();

        // Redirect back to index
        return Redirect::to(URL::current())->with('success',
            __('decoy::elements.successfully_saved'));
    }

    /**
     * Handle storage of image Element types
     *
     * @param  Element $el
     * @param  array   $input
     * @return void
     */
    protected function storeImage(Element $el, $input)
    {
        // Do nothing if no images
        if (!is_array($input['images'])) {
            return;
        }

        // Check for the image in the input.  If isn't found, make no changes.
        $name = $el->inputName();
        if (!$data = array_first($input['images'],
            function ($data, $id) use ($name) {
                return $data['name'] == $name;
            })) {
            return;
        }

        // Update an existing image
        if ($image = $el->images->first()) {
            $image->fill($data)->save();

        // Or create a new image, if file data was supplied
        } elseif (!empty($data['file'])) {
            $el->value = null;
            $el->save();
            $image = new Image($data);
            $el->images()->save($image);

        // Othweise, nothing to do
        } else {
            return;
        }

        // Update the element with the path to the image
        $el->value = $image->file;
        $el->save();
    }

    /**
     * Show the field editor form that will appear in an iframe on
     * the frontend
     *
     * @param  string                   $key A full Element key
     * @return Illuminate\Http\Response
     */
    public function field($key)
    {
        return View::make('decoy::layouts.blank')
            ->nest('content', 'decoy::elements.field', [
                'element' => app('decoy.elements')
                    ->localize(Decoy::locale())
                    ->hydrate(true)
                    ->get($key),
            ]);
    }

    /**
     * Update a single field because of a frontend Element editor
     * iframe post
     *
     * @param  string                   $key A full Element key
     * @return Illuminate\Http\Response
     */
    public function fieldUpdate($key)
    {
        $elements = app('decoy.elements')->localize(Decoy::locale());

        // If the value has changed, update or an insert a record in the database.
        $el = Decoy::el($key);
        $value = request('value');
        if ($value != $el->value || Request::hasFile('value')) {

            // Making use of the model's exists property to trigger Laravel's
            // internal logic.
            $el->exists = $elements->keyUpdated($el->key);

            // Save it.  Files will be automatically attached via model callbacks
            $el->value = $value;
            $el->locale = Decoy::locale();
            $el->save();

            // Clear the cache
            $elements->clearCache();
        }

        // Return the layout with JUST a script variable with the element value
        // after saving.  Thus, post any saving callback operations.
        return View::make('decoy::layouts.blank', [
            'content' => "<div id='response' data-key='{$key}'>{$el}</div>"
        ]);
    }

    /**
     * The permissions options are a list of all the tabs
     *
     * @return array
     */
    public function getPermissionOptions()
    {
        // Get all the grouped elements
        $elements = app('decoy.elements')
            ->localize(Decoy::locale())
            ->hydrate(true)
            ->asModels()
            ->sortBy('page_label')
            ->groupBy('page_label');

        // Map to the expected permisions forat
        $out = [];
        foreach ($elements as $page_label => $fields) {
            $out[Str::slug($page_label)] = [$page_label, $fields[0]->page_help];
        }

        return $out;
    }

    /**
     * Populate protected properties on init
     */
    public function __construct()
    {
        $this->title = __('decoy::elements.controller.title');
        $this->description = __('decoy::elements.controller.description');

        parent::__construct();
    }
}
