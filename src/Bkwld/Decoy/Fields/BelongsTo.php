<?php namespace Bkwld\Decoy\Fields;

// Dependencies
use Former\Traits\Field;
use HtmlObject\Input as HtmlInput;
use Illuminate\Container\Container;

/**
 * Create an autocomplete field that populates a foreign key in a 
 * belongs to relationship
 */
class BelongsTo extends Field {

	/**
	 * Preserve the route
	 *
	 * @var string
	 */
	private $route;

	/**
	 * Properties to be injected as attributes
	 *
	 * @var array
	 */
	protected $injectedProperties = array('type', 'name', 'value');

	/**
	 * Build a text field but add some default stuff that benefits autocompletes
	 *
	 * @param Container $app        The Illuminate Container
	 * @param string    $type       text
	 * @param string    $name       Field name, usually the column name of the foreign key
	 * @param string    $label      Its label
	 * @param string    $value      Its value
	 * @param array     $attributes Attributes
	 */
	public function __construct(Container $app, $type, $name, $label, $value, $attributes) {

		// Set default attributes
		$attributes = array_merge(array(
			'class' => 'span5',
			'placeholder' => 'Search',
			'autocomplete' => 'off',
		), (array) $attributes);

		// Create a text type field
		parent::__construct($app, 'text', $name, $label, $value, $attributes);

	}

	/**
	 * Store the The GET route that will return data for the autocomplete. The response 
	 * should be an array of key / value pairs where the key is what will get submitted 
	 * and the value is the title that is displayed to users.
	 *
	 * @param string $route 
	 */
	public function route($route) {
		$this->route = $route;
		return $this;
	}

	/**
	 * Prints out the field, wrapped in its group.  This is the opportunity
	 * to tack additional stuff onto the control group
	 * 
	 * @return string
	 */
	public function wrapAndRender() {

		// Apend the edit button
		$this->appendEditButton();

		// Add control group attributes
		$this->addGroupClass('belongs-to');
		$this->group->setAttribute('data-js-view', 'belongs-to');
		if ($this->route) $this->group->setAttribute('data-controller-route', $this->route);

		// Continue doing normal wrapping
		return parent::wrapAndRender();

	}

	/**
	 * Append a different edit button depending on whether there is a value
	 * already set
	 */
	protected function appendEditButton() {
		if ($this->value) $this->append('<button type="button" class="btn btn-info">
				<span class="glyphicon glyphicon-pencil"></span>
			</button>');
		else $this->append('<button type="button" class="btn" disabled>
				<span class="glyphicon glyphicon-ban-circle"></span>
			</button>');
	}

	/**
	 * Prints out the current tag, appending an extra hidden field onto it
	 * for the storing of the foreign key
	 *
	 * @return string An input tag
	 */
	public function render() {

		// Always add this class
		$this->addClass('autocomplete');

		// Add the hidden field and return
		return parent::render().$this->renderHidden();
	}

	/**
	 * Render the hidden field that contains the previous value
	 *
	 * @return string A hidden field
	 */
	protected function renderHidden() {
		return HtmlInput::hidden($this->name, $this->value);
	}

}