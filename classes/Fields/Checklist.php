<?php

namespace Bkwld\Decoy\Fields;

// Deps
use Bkwld\Library\Laravel\Former as FormerUtils;
use Former\Form\Fields\Checkbox;
use HtmlObject\Input as HtmlInput;
use Illuminate\Container\Container;

/**
 * A note field has no actual input elements.  It's a control group with just the passed
 * html where the inputs would be.
 */
class Checklist extends Checkbox
{
    /**
     * Build a new checkable
     *
     * @param Container $app
     * @param string    $type
     * @param array     $name
     * @param           $label
     * @param           $value
     * @param           $attributes
     */
    public function __construct(Container $app, $type, $name, $label, $value, $attributes)
    {
        // Make Former treat this commponent like a regular checkbox field
        if ($type == 'checklist') $type = 'checkbox';
        parent::__construct($app, $type, $name, $label, $value, $attributes);
    }

    /**
     * Accept checkbox configuration from an associative array
     *
     * @param array $options
     * @return $this
     */
    public function from(array $options)
    {
        // Makes the options passed in override anything from the populator.
        $this->grouped = true;

        // Produce Former-style checkbox options
        $options = FormerUtils::checkboxArray($this->name, $options);
        $this->checkboxes($options);

        // Make chainable
        return $this;
    }

    /**
     * Prepend a hidden field of the same name to the checkboxes so that if none
     * are checked, they get cleared.  This is an alternative to the Former
     * push() which resulted in an array of multiple NULL values when the field
     * is stored using Laravel casting to array.
     *
     * @return string An input tag
     */
    public function render()
    {
        return HtmlInput::hidden($this->name).parent::render();
    }
}
