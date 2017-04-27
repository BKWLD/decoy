<?php

namespace Bkwld\Decoy\Fields;

// Deps
use Bkwld\Library\Laravel\Former as FormerUtils;
use Former\Form\Fields\Radio;
use Illuminate\Container\Container;

/**
 * A note field has no actual input elements.  It's a control group with just the passed
 * html where the inputs would be.
 */
class Radiolist extends Radio
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
        // Make Former treat this commponent like a regular radio field
        if ($type == 'radiolist') $type = 'radio';
        parent::__construct($app, $type, $name, $label, $value, $attributes);
    }

    /**
     * Accept radio configuration from an associative array
     *
     * @param array $options
     * @return $this
     */
    public function from(array $options)
    {
        return $this->radios(FormerUtils::radioArray($options));
    }
}
