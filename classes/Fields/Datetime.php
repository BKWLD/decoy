<?php

namespace Bkwld\Decoy\Fields;

use Former;
use Bkwld\Library;
use Former\Traits\Field;
use HtmlObject\Input as HtmlInput;
use Illuminate\Container\Container;

/**
 * Combine the date and time fields into a single control group (thus the
 * control group is actually generated via JS; see comments below)
 */
class Datetime extends Field
{
    /**
     * Store the date field instance
     *
     * @var Bkwld\Decoy\Fields\Date
     */
    private $date;

    /**
     * Store the time field instance
     *
     * @var Bkwld\Decoy\Fields\Time
     */
    private $time;

    /**
     * Store the passed choices but ultimately a unique field won't be built.  Instead,
     * we'll build two sub fields (including their control group), and add a little extra HTML.
     * JS will cleanup the markup we're not actually using.
     *
     * We are rendering the sub fields (date and time) with their control groups so that their
     * framework specific stuff (append()) gets rendered.  It's not very easy to pull that data
     * out without rendering the control group because most of those methods are protected.  I
     * don't want to subclass Group and make sure the subclass is used since I already have a
     * JS solution working.
     *
     * @param Container $app        The Illuminate Container
     * @param string    $type       'datetime'
     * @param string    $name       Field name
     * @param string    $label      Its label
     * @param string    $value      Its value
     * @param array     $attributes Attributes
     */
    public function __construct(Container $app, $type, $name, $label, $value, $attributes)
    {
        parent::__construct($app, 'text', $name, $label, $value, $attributes);

        // Store the individual instances
        $this->date = Former::date($name, $label, $value, $attributes);
        $this->time = Former::time($name, $label, $value, $attributes);

        // Don't render the group class for this field.
        $this->group->raw();
    }

    /**
     * Massage inputted string values into dates
     *
     * @param  string $value A new value
     * @return $this
     */
    public function value($value)
    {
        // Set the value using parent, so that if there is already one specified
        // from populate or POST
        parent::value($value);

        // Set the value of the subfields
        $this->date->value($value);
        $this->time->value($value);

        // Chainable
        return $this;
    }

    /**
     * Set blockhelp on the time field.  The JS only shows it's help
     *
     * @return $this
     */
    public function blockHelp($text)
    {
        $this->time->blockHelp($text);

        return $this;
    }

    /**
     * Configure the input attibutes for the date field
     */
    public function date($attributes)
    {
        $this->date->setAttributes($attributes);

        return $this;
    }

    /**
     * Configure the input attibutes for the date field
     */
    public function time($attributes)
    {
        $this->time->setAttributes($attributes);

        return $this;
    }

    /**
     * Prints out the current tag
     *
     * @return string An input tag
     */
    public function render()
    {
        // Convert the value to a mysql friendly format or leave null.
        $mysql_date = $this->value ?
            date(Library\Utils\Constants::MYSQL_DATETIME, strtotime($this->value)) :
            null;

        // Add a hidden field that will contain the mysql value, for storing in db
        $hidden = HtmlInput::hidden($this->name, $mysql_date)
            ->class('datetime')
            ->id($this->name);

        // Return html
        return '<div class="datetime-field">'
            .$this->date->wrapAndRender()
            .$this->time->wrapAndRender()
            .$hidden
            .'</div>';
    }
}
