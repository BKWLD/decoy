<?php namespace Bkwld\Decoy\Fields;

// Dependencies
use Bkwld\Library;
use Former\Traits\Field;
use HtmlObject\Input as HtmlInput;
use Illuminate\Container\Container;

/**
 * Create a date input that uses a bootstrap plugin to generate
 * a selectable calendar widget
 */
class Date extends Field {

	/**
	 * Properties to be injected as attributes
	 *
	 * @var array
	 */
	protected $injectedProperties = array('type', 'name', 'value');

	/**
	 * Build a text field but add some default stuff that benefits dates
	 *
	 * @param Container $app        The Illuminate Container
	 * @param string    $type       text
	 * @param string    $name       Field name
	 * @param string    $label      Its label
	 * @param string    $value      Its value
	 * @param array     $attributes Attributes
	 */
	public function __construct(Container $app, $type, $name, $label, $value, $attributes) {

		// Set default attributes
		$attributes = array_merge(array(
			'class' => 'col-xs-2',
			'maxlength' => 10,
			'placeholder' => 'MM/DD/YY',
			'id' => null, // We don't want to conflict on the id
		), (array) $attributes);

		// Create a text type field
		parent::__construct($app, 'text', $name, $label, $value, $attributes);

		// Part of the parent's constructor populates the value field using posted
		// or populated data.  If there is a value now (or if there was before), make
		// it human readable.  This assumes it WAS a MYSQL timestamp.
		if ($this->value) $this->value = date('m/d/Y', strtotime($this->value));

		// Apend the button that the calendar selector hooks into
		$this->append('<i class="glyphicon glyphicon-calendar"></i>');

	}

	/**
	 * Massage inputted string values into dates
	 *
	 * @param  string $value A new value
	 */
	public function value($value) {
		return parent::value(date('m/d/Y', strtotime($value)));
	}

	/**
	 * Prints out the current tag
	 *
	 * @return string An input tag
	 */
	public function render() {

		// Always add this class
		$this->addClass('date');

		// Create HTML string
		$html = parent::render();

		// Convert the value to a mysql friendly format or leave null.
		$mysql_date = $this->value ? 
			date(Library\Utils\Constants::MYSQL_DATE, strtotime($this->value)) : 
			null;

		// Add a hidden field that will contain the mysql value, for storing in db
		$html .= HtmlInput::hidden($this->name, $mysql_date)
			->class('date')
			->id($this->name);

		// Return the string
		return $html;
	}

}