<?php

namespace Bkwld\Decoy\Fields;

// Deps
use Bkwld\Library\Laravel\Former as FormerUtils;
use Former\Form\Fields\Checkbox;
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
        // Produce Former-style checkbox options
        $options = FormerUtils::checkboxArray($this->name, $options);
        $this->checkboxes($options);

        // Prevent hidden fields from confusing repopulation on errors
        // http://cl.ly/image/3l1D32021q2I
        $this->push(false);

        // Make chainable
        return $this;
    }
}
