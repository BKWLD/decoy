<?php

namespace Bkwld\Decoy\Fields;

use Croppa;
use Former;
use Bkwld\Library\Utils;
use Former\LiveValidation;
use Former\Form\Fields\File;
use Bkwld\Decoy\Models\Element;
use Illuminate\Container\Container;
use Bkwld\Decoy\Models\Image as ImageModel;

/**
 * Creates an image file upload field with addtional UI for displaing previously
 * uploaded images, setting crop bounds, and deleting them.
 */
class Image extends File
{
    use Traits\InsertMarkup, Traits\Helpers;

    /**
     * Whether to show the focal point UI
     *
     * @var bool
     */
    private $add_focal_point = false;

    /**
     * The unique id for this Image instance
     *
     * @var string
     */
    private $input_id;

    /**
     * The aspect ratio jCrop should use
     *
     * @var number
     */
    private $ratio;

    /**
     * Create an image upload field
     *
     * @param Container $app        The Illuminate Container
     * @param string    $type       text
     * @param string    $name       Field name
     * @param string    $label      Its label
     * @param string    $value      Its value
     * @param array     $attributes Attributes
     */
    public function __construct(Container $app, $type, $name, $label, $value, $attributes)
    {
        parent::__construct($app, 'file', $name, $label, $value, $attributes);

        // Set a default label if there is a NULL name
        // DEPRECATED
        if (!$name) {
            $this->group->setLabel('Image');
        }

        // Add an extra class to allow styles to tweak ontop of the main "uploads"
        // class
        $this->addGroupClass('image-upload');

        // Make it accept only images
        $this->accept('image');

        // Add the max upload info
        $this->addClass('js-tooltip');
        $this->title('Max upload size: <b>'
            .Utils\Text::humanSize(Utils\File::maxUpload(), 1).'</b>');

        // Add validation rules
        $this->applyRules();
    }

    /**
     * Apply validation rules
     *
     * @return void
     */
    protected function applyRules()
    {
        // Check if there are rules for this field
        $rules_key = 'images.' . ($this->name ?: 'default');
        if (!$rules = Former::getRules($rules_key)) {
            return;
        }

        // If there already is an image, drop the forced requirement
        if ($this->hasImage() && array_key_exists('required', $rules)) {
            unset($rules['required']);
            $this->addClass('required'); // So the icon shows up
        }

        // Apply rules using Former's LiveValidation
        $live = new LiveValidation($this);
        $live->apply($rules);
    }

    /**
     * Show errors that will have been stored on the `file` proeperty
     *
     * @return void
     */
    public function showErrors()
    {
        $key = sprintf('images.%s.file', $this->inputId());
        if (session('errors') && ($error = session('errors')->first($key))) {
            $this->help($error);
            $this->group->addClass('has-error');
        }
    }

    /**
     * Give the file input the prefixed name.
     *
     * @return string An input tag
     */
    public function render()
    {
        $this->name = $this->inputName('file');

        return parent::render();
    }

    /**
     * Prints out the field, wrapped in its group.  Additional review UI is tacked
     * on here.
     *
     * @return string
     */
    public function wrapAndRender()
    {
        // Get additional UI first so it can modify the normal form-group UI.
        $html = $this->renderEditor();

        // Add the aspect ratio choice
        $this->group->dataAspectRatio($this->ratio ?: false);

        // Set errors
        $this->showErrors();

        // Inform whether there is an existing image to preview
        if ($this->hasImage()) {
            $this->group->addClass('has-image');
        }

        // Add extra markup
        return $this->appendToGroup(parent::wrapAndRender(), $html);
    }

    /**
     * Render the display of the currently uploaded item
     *
     * @return string HTML
     */
    protected function renderEditor()
    {
        $html = '';

        // Will store the name
        $html .= $this->createHidden('name', $this->name);

        // Will store the crop coordinates
        $html .= $this->createHidden('crop_box',
            $this->image()->getAttributeValue('crop_box'));

        // Begin the image upload section
        $html .= '
			<div class="image-holder">
			<div class="toolbar input-group">
			<div class="btn-group btn-group-sm">
			<div class="crop btn js-tooltip active" title="' . __('decoy::form.image.crop_tooltip') . '">
			<div class="glyphicon glyphicon-scissors"></div></div>';

        // Will add the focal point ui
        if ($this->add_focal_point) {
            $html .= '<div class="focal btn js-tooltip" title="' . __('decoy::form.image.focal_tooltip') . '">
				<div class="glyphicon glyphicon-screenshot"></div></div>';

            // Will store the focal point
            $html .= $this->createHidden('focal_point',
                $this->image()->getAttributeValue('focal_point'));
        }

        // Will close out the button group
        $html .= '</div>';

        // Add the title input
        $html .= Former::text($this->inputName('title'))
            ->class('title js-tooltip')
            ->placeholder(__('decoy::form.image.title_placeholder'))
            ->title(__('decoy::form.image.title_help'))
            ->render();

        // Add delete button
        $html .= '<div class="delete btn-sm js-tooltip">
				<div class="glyphicon glyphicon-trash"></div>
			</div>';

        // Close out all divs and add image holder.  Make the old image no wider
        // than the max available width of forms (1050)
        if ($src = Former::getValue($this->inputName('file'))) {
            $src = Croppa::url($src, 1050);
        }

        $html .= sprintf('</div>
			<div class="preview"><img class="source" src="%s"></div>
			</div>', $src);

        return $html;
    }

    /**
     * Check if there is already an upload
     *
     * @return boolean
     */
    protected function hasImage()
    {
        return Former::getValue($this->inputName('file'));
    }

    /**
     * Make a hidden field
     *
     * @param  string $name
     * @param  string $value
     * @return string
     */
    protected function createHidden($name, $value = null)
    {
        $field = Former::hidden($this->inputName($name))->class('input-'.$name);
        if ($value) {
            if (!is_scalar($value)) {
                $value = json_encode($value);
            }
            $field->forceValue($value);
        }

        return $field->render();
    }

    /**
     * Wrap an input name in the nested / array-like structure
     *
     * @param  string $name
     * @return string
     */
    protected function inputName($name)
    {
        return sprintf('images[%s][%s]', $this->inputId(), $name);
    }

    /**
     * Get the id to use in all inputs for this Image
     *
     * @return string Either a model id or an arbitrary number prefixed by _
     */
    protected function inputId()
    {
        // If we're editing an already existing image, return it's Image id
        if (($image = $this->image()) && $image->id) {
            return $image->id;
        }

        // Otherwise, use the name of the field, which should be unique compared
        // to other image fields on the page.
        if (!$this->input_id) {
            $this->input_id = '_'.preg_replace('#[^\w]#', '', $this->name);
        }

        return $this->input_id;
    }

    /**
     * Get the Image model we're editing or an empty Image class
     *
     * @return Image
     */
    protected function image()
    {
        if (!$model = $this->getModel()) {
            return new ImageModel;
        }

        return $model->img($this->name);
    }

    /**
     * Store the aspect ratio to use with jCrop
     *
     * @param  number $ratio The result of an expression like 16/9
     * @return $this
     */
    public function aspect($ratio)
    {
        $this->ratio = $ratio;

        return $this;
    }

    /**
     * Toggle the presence of the focal point ui
     *
     * @param  bool  $bool
     * @return $this
     */
    public function addFocalPoint($bool = true)
    {
        $this->add_focal_point = $bool;

        return $this;
    }

    /**
     * Logic for rendering an Image field for editing Element images
     *
     * @param  Element $element
     * @return $this
     */
    public function forElement(Element $el)
    {
        // Add help
        $this->model($el)->blockhelp($el->help);

        // Populate the image array with the image instance.  This will create an
        // Image instance from the default value if it doesn't exist.
        $pop = app('former.populator')->all();
        $pop['images'][$this->inputId()] = $el->img();
        app('former.populator')->replace($pop);

        // Support chaining
        return $this;
    }
}
