<?php

namespace Bkwld\Decoy\Fields;

use Bkwld\Library;
use Former\Traits\Field;
use HtmlObject\Input as HtmlInput;
use Illuminate\Container\Container;

/**
 * Create a time input that uses a bootstrap plugin to generate
 * a selectable calendar widget
 */
class Time extends Field
{
    /**
     * Properties to be injected as attributes
     *
     * @var array
     */
    protected $injectedProperties = ['type', 'name', 'value'];

    /**
     * Build a text field but add some default stuff that benefits times
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
        // Set default attributes
        $attributes = array_merge([
            'maxlength' => 8,
            'placeholder' => 'HH:MM',
            'id' => null, // We don't want to conflict on the id
        ], (array) $attributes);

        // Create a text type field
        parent::__construct($app, 'text', $name, $label, $value, $attributes);
        $this->addGroupClass('time-field');

        // Part of the parent's constructor populates the value field using posted
        // or populated data.  If there is a value now (or if there was before), make
        // it human readable.  This assumes it WAS a MYSQL timestamp.
        if ($this->value) {
            $this->value = date('h:i A', strtotime($this->value));
        }

        // Add some default blockhelp about the timezone
        $this->blockHelp('Time is in '.date('T'));

        // Apend the button that the calendar selector hooks into
        $this->append('<span class="glyphicon glyphicon-time"></span>');
    }

    /**
     * Massage inputted string values into dates
     *
     * @param string $value A new value
     */
    public function value($value)
    {
        return parent::value(date('h:i A', strtotime($value)));
    }

    /**
     * Prints out the current tag
     *
     * @return string An input tag
     */
    public function render()
    {
        // Always add this class
        $this->addClass('time');

        // Create HTML string
        $html = parent::render();

        // Convert the value to a mysql friendly format or leave null.
        $mysql_date = $this->value ?
            date(Library\Utils\Constants::MYSQL_TIME, strtotime($this->value)) :
            null;

        // Add a hidden field that will contain the mysql value, for storing in db
        $html .= HtmlInput::hidden($this->name, $mysql_date)->id($this->name);

        // Return the string
        return $html;
    }
}
