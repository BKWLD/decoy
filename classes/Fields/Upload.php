<?php

namespace Bkwld\Decoy\Fields;

use Bkwld\Library\Utils;
use Former\Form\Fields\File;
use Illuminate\Container\Container;

/**
 * Creates a file upload field with addtional UI for reviewing the last upload
 * and deleting it.
 */
class Upload extends File
{
    use Traits\InsertMarkup;

    /**
     * Create a regular file type field
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
        $this->addGroupClass('upload');

        // Add the max upload info
        $this->addClass('js-tooltip');
        $this->title('Max upload size: <b>'
            .Utils\Text::humanSize(Utils\File::maxUpload(), 1).'</b>');
    }

    /**
     * Prints out the field, wrapped in its group.  Additional review UI is tacked on here.
     *
     * @return string
     */
    public function wrapAndRender()
    {
        // Get additional UI first so it can modify the normal form-group UI.
        $html = $this->renderReview();

        // Add extra markup
        return $this->appendToGroup(parent::wrapAndRender(), $html);
    }

    /**
     * Prints out the current file upload field.
     *
     * @return string An input tag
     */
    public function render()
    {
        // A file has already been uploaded
        if ($this->value) {

            // If it's required, show the icon but don't enforce it.  There is already
            // a file uploaded after all
            if ($this->isRequired()) {
                $this->setAttribute('required', null);
            }
        }

        // Continue execution
        return parent::render();
    }

    /**
     * Render the display of the currently uploaded item
     *
     * @return string HTML
     */
    protected function renderReview()
    {
        if (!$this->value) {
            return;
        }

        if (!$this->isRequired() && $this->isInUploads()) {
            return $this->renderDestuctableReview();
        }

        return $this->renderIndestructibleReview();
    }

    /**
     * Check if the file is in the uploads directory. The use case for this arose
     * with the Elements system where the default images would usually be in the
     * img directory
     *
     * @return boolean
     */
    protected function isInUploads()
    {
        return app('upchuck')->manages($this->value);
    }

    /**
     * Show the preview UI with a delete checkbox
     *
     * @return string HTML
     */
    protected function renderDestuctableReview()
    {
        return '<label for="'.$this->name.'-delete" class="checkbox-inline upload-delete">
            <input id="'.$this->name.'-delete" type="checkbox" name="'.$this->name.'" value="">
            Delete '.$this->renderDownloadLink().'
            </label>';
    }

    /**
     * Show the preview UI withOUT a delete checkbox
     *
     * @return string HTML
     */
    protected function renderIndestructibleReview()
    {
        return '<label class="download">
            Currently '.$this->renderDownloadLink().'
            </label>';
    }

    /**
     * Make the downloadable link to the file
     *
     * @return string HTML
     */
    protected function renderDownloadLink()
    {
        return '<a class="download-link" href="'.$this->value.'">
            <span class="glyphicon glyphicon-file"></span>'.basename($this->value).'
            </a>';
    }
}
