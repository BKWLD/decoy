<?php

namespace Bkwld\Decoy\Fields;

use Decoy;
use DecoyURL;
use Former\Traits\Field;
use Illuminate\Support\Str;
use HtmlObject\Input as HtmlInput;
use Illuminate\Container\Container;

/**
 * Create an autocomplete field that populates a foreign key in a
 * belongs to relationship
 */
class BelongsTo extends Field
{
    use Traits\Helpers;

    /**
     * Preserve the relation
     *
     * @var string
     */
    private $relation;

    /**
     * Preserve the route
     *
     * @var string
     */
    private $route;

    /**
     * Preserve the title, what shows up in the autocomplete
     *
     * @var string
     */
    private $title;

    /**
     * Properties to be injected as attributes
     *
     * @var array
     */
    protected $injectedProperties = ['type', 'name', 'value'];

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
    public function __construct(Container $app, $type, $name, $label, $value, $attributes)
    {
        // Set default attributes
        $attributes = array_merge([
            'class' => 'span5',
            'placeholder' => __('decoy::form.belongs_to.search'),
            'autocomplete' => 'off',
        ], (array) $attributes);

        // Create a text type field
        parent::__construct($app, 'text', $name, $label, $value, $attributes);
    }

    /**
     * Allow the relation to be explicitly specified if guessing won't work.
     *
     * @param string $route
     */
    public function relation($relation)
    {
        $this->relation = $relation;

        return $this;
    }

    /**
     * Store the The GET route that will return data for the autocomplete. The
     * response  should be an array of key / value pairs where the key is what
     * will get submitted and the value is the title that is displayed to users.
     *
     * @param string $route
     */
    public function route($route)
    {
        $this->route = $route;

        return $this;
    }

    /**
     * Use the parent class to set the route
     *
     * @param string $class
     */
    public function parent($class)
    {
        $this->route(DecoyURL::action(Decoy::controllerForModel($class)));

        return $this;
    }

    /**
     * Store the title for the autocomplete, useful if pre-populating the
     * autocomplete on the create page.
     *
     * @param string $title
     */
    public function title($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Prints out the field, wrapped in its group.  This is the opportunity
     * to tack additional stuff onto the control group
     *
     * @return string
     */
    public function wrapAndRender()
    {
        // Apend the edit button
        $this->appendEditButton();

        // Add control group attributes
        $this->addGroupClass('belongs-to');
        $this->group->setAttribute('data-js-view', 'belongs-to');
        if ($this->route) {
            $this->group->setAttribute('data-controller-route', $this->route);
        }

        // Continue doing normal wrapping
        return parent::wrapAndRender();
    }

    /**
     * Append a different edit button depending on whether there is a value
     * already set
     */
    protected function appendEditButton()
    {
        if ($this->value) {
            $this->append('<button type="button" class="btn btn-default">
				<span class="glyphicon glyphicon-pencil"></span>
			</button>');
        } else {
            $this->append('<button type="button" class="btn btn-default" disabled>
				<span class="glyphicon glyphicon-ban-circle"></span>
			</button>');
        }
    }

    /**
     * Prints out the current tag, appending an extra hidden field onto it
     * for the storing of the foreign key
     *
     * @return string An input tag
     */
    public function render()
    {
        // Always add this class
        $this->addClass('autocomplete');

        // Make the hidden field.  Render it before we (might) set the value to
        // the title of the parent.
        $hidden = $this->renderHidden();

        // If there is a value and the field name matches a relationship on function
        // on the current item, then get and display the title text for the related
        // record
        if ($parent = $this->parentModel()) {
            $this->value = $parent->getAdminTitleAttribute();

        // Else, if there is no parent (it's a create page), set the value if a
        // title was set
        } elseif ($this->title) {
            $this->value = $this->title;
        }

        // Add the hidden field and return
        return parent::render().$hidden;
    }

    /**
     * Get the parent record of the form instance
     *
     * @return Illuminate\Database\Eloquent\Model
     */
    public function parentModel()
    {
        if ($this->value
            && ($relation = $this->guessRelation())
            && ($model = $this->getModel())
            && method_exists($model, $relation)) {
            return $model->$relation;
        }
    }

    /**
     * Guess at the relationship name by removing id from the name and camel casing
     *
     * @return string
     */
    protected function guessRelation()
    {
        if ($this->relation) {
            return $this->relation;
        }

        return Str::camel(str_replace('_id', '', $this->name));
    }

    /**
     * Render the hidden field that contains the previous value
     *
     * @return string A hidden field
     */
    protected function renderHidden()
    {
        return HtmlInput::hidden($this->name, $this->value);
    }
}
