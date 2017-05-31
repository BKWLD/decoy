<?php

namespace Bkwld\Decoy\Fields;

// Deps
use Former\Form\Fields\Checkbox;
use HtmlObject\Input as HtmlInput;
use Illuminate\Container\Container;

/**
 * A note field has no actual input elements.  It's a control group with just the passed
 * html where the inputs would be.
 */
class Boolean extends Checkbox
{
    /**
     * The label that is shown next to the checkbox
     *
     * @var string
     */
    protected $message = 'Yes';

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
        parent::__construct($app, 'checkbox', $name, $label, $value, $attributes);
    }

    /**
     * Prepend a hidden field with a value of 0.  This is similar to Former's
     * push() but with an actual value so the field doesn't need to be nullable.
     *
     * @return string An input tag
     */
    public function render()
    {
        $this->makeBooleanCheckbox();
        return HtmlInput::hidden($this->name, '0').parent::render();
    }

    /**
     * Set the CTA value
     *
     * @param  string $message
     * @return $this
     */
    public function message($message)
    {
        $this->message = $message;
        return $this;
    }

    /**
     * Make the boolean-style checkbox immediately before render
     *
     * @return void
     */
    protected function makeBooleanCheckbox()
    {
        $this->checkboxes([
            $this->message => [
                'name' => $this->name,
                'value' => 1,
            ]
        ]);
    }
}
