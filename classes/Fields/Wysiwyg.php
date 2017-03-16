<?php

namespace Bkwld\Decoy\Fields;

use Former\Form\Fields\Textarea;
use Illuminate\Container\Container;

/**
 * Essentially a textarea with the wysiwyg class applied, which Decoy JS
 * turns into a Wywsiwyg editor
 */
class Wysiwyg extends Textarea
{
    /**
     * Create a standard and then add the class
     *
     * @param Container $app        The Illuminate Container
     * @param string    $type       text
     * @param string    $name       Its name
     * @param string    $label      Its label
     * @param string    $value      Its value
     * @param array     $attributes Attributes
     */
    public function __construct(Container $app, $type, $name, $label, $value, $attributes)
    {
        parent::__construct($app, $type, $name, $label, $value, $attributes);

        $this->addClass('wysiwyg');
    }
}
