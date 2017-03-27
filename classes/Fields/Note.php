<?php

namespace Bkwld\Decoy\Fields;

use HtmlObject\Element;
use Former\Traits\Field;
use Illuminate\Container\Container;

/**
 * A note field has no actual input elements.  It's a control group with just the passed
 * html where the inputs would be.
 */
class Note extends Field
{
    /**
     * Create a standard field
     *
     * @param Container $app        The Illuminate Container
     * @param string    $type       text
     * @param string    $label      Its label
     * @param string    $value      Its value
     * @param array     $attributes Attributes
     */
    public function __construct(Container $app, $type, $label, $value, $attributes)
    {
        // Make setting a name unncessary
        parent::__construct($app, $type, '', $label, $value, $attributes);

        // Add a class to the group for extra styling
        $this->addClass('form-control-static');
    }

    /**
     * Output the value in a normal div
     *
     * @return string An input tag
     */
    public function render()
    {
        // Remove default class
        $this->removeClass('form-control');

        // Render the element
        return Element::create('p', $this->value, $this->attributes);
    }
}
