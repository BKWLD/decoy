<?php

namespace Bkwld\Decoy\Fields;

use URL;
use View;
use Decoy;
use Former;
use Request;
use DecoyURL;
use Former\Traits\Field;
use Illuminate\Support\Str;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Create a table-layout listing of records in the database.  An index view.  It may
 * be presented in different styles.  Example:
 *
 * echo Former::listing('{model class}') // IE. 'Article'
 *  ->layout('sidebar')
 *  ->scope(function($query) { return $query->where('category' ,'=', 'example') })
 *  ->take(20)
 */
class Listing extends Field
{
    use Traits\CaptureLabel, Traits\Scopable, Traits\Helpers, Traits\CaptureHelp;

    /**
     * Override Former Field and Tag properties to wrap the listing inside of
     * another div that can have classes and such added onto them.
     *
     * @var string
     */
    protected $element = 'div';

    /**
     * @var bool
     */
    protected $isSelfClosing = false;

    /**
     * @var array
     */
    protected $injectedProperties = [];

    /**
     * Preserve the controller
     *
     * @var Bkwld\Decoy\Controller\Base
     */
    private $controller;

    /**
     * Preserve the controller class name
     *
     * @var string
     */
    private $controller_name;

    /**
     * Preserve the items
     *
     * @var LengthAwarePaginator|Collection|string $items
     */
    private $items;

    /**
     * Preserve the layout
     *
     * @var string
     */
    private $layout = 'full';

    /**
     * Preserve the parent
     *
     * @var Illuminate\Database\Eloquent\Model
     */
    private $parent_item;

    /**
     * Preserve the amount to take
     *
     * @var int
     */
    private $take;

    /**
     * Set up a Field instance.  Note, the model class name is passed in place of the nemae
     *
     * @param Container $app        The Illuminate Container
     * @param string    $type       Not really used, but kept for inheritance sake
     * @param string    $model      The model class name
     * @param string    $label      Its label
     * @param string    $value      Its value
     * @param array     $attributes Attributes
     * @param array     $config     Additional parameters passed by factories
     */
    public function __construct(Container $app, $type, $model, $label, $value, $attributes, $config)
    {
        // Get the parent item
        $this->parent_item = $this->getModel();

        // Instantiate a controller given the model name
        $this->controller(isset($config['controller'])
            ? $config['controller']
            : Decoy::controllerForModel($model)
        );

        // If no title is passed, set it to the controller name
        if (!$label && $this->controller) {
            $label = $this->controller->title();
        } elseif (!$label) {
            $label = Str::plural($model);
        }

        // Add passed items
        if (isset($config['items'])) {
            $this->items($config['items']);
        }

        // Continue instantiation
        parent::__construct($app, $type, $model, $label, $value, $attributes);
    }

    /**
     * A factory to create an instance using the passed controller.  This is to prevent
     * duplicate controller instantations when invoked from the base controller.
     *
     * @param  Bkwld\Decoy\Controllers\Base $controller
     * @param  LengthAwarePaginator         $items
     * @return Bkwld\Decoy\Field\Listing
     */
    public static function createFromController($controller, $items)
    {
        $model = $controller->model();

        return Former::listing($model, null, null, null, [
            'controller' => $controller,
            'items' => $items,
        ]);
    }

    /**
     * Replace the controller
     *
     * @param  Bkwld\Decoy\Controllers\Base | string $controller
     * @return Field                                 This field
     */
    public function controller($controller)
    {

        // Instantiate a string controller
        if (is_string($controller)
            && class_exists($controller)
            && is_subclass_of($controller, 'Bkwld\Decoy\Controllers\Base')) {
            $this->controller_name = $controller;
            $this->controller = new $controller;

            // Apply the parent if one was set
            if ($this->parent_item && $this->controller) {
                $this->controller->parent($this->parent_item);
            }

        // Or, validate a passed controller instance
        } elseif (is_object($controller)
            && is_a($controller, 'Bkwld\Decoy\Controllers\Base')) {
            $this->controller_name = get_class($controller);
            $this->controller = $controller;
        }

        // Chain
        return $this;
    }

    /**
     * Store the items that will be displayed in the list
     *
     * @param  LengthAwarePaginator | Collection | $items
     * @return Field                               This field
     */
    public function items($items)
    {
        $this->items = $items;

        return $this;
    }

    /**
     * Store the layout choice.  Possibilities
     * - full (default)
     * - sidebar
     * - form
     *
     * @param  string $layout
     * @return Field  This field
     */
    public function layout($layout)
    {
        // Save the layout
        if ($layout == 'control group') {
            $layout = 'form';
        }

        // Backwards compat
        if (in_array($layout, ['full', 'sidebar', 'form'])) {
            $this->layout = $layout;
        }

        // Make the default width of form layout a span9.  Because typically the form
        // layout is only used on full width forms
        if ($layout == 'form') {
            $this->addClass('span9');
        }

        // Chain
        return $this;
    }

    /**
     * Store the parent model instance
     *
     * @param  Illuminate\Database\Eloquent\Model $parent
     * @return this
     */
    public function parent($parent)
    {
        $this->parent_item = $parent;

        return $this;
    }

    /**
     * Store the number of items to fetch, the per_page
     *
     * @param  int   $take
     * @return Field This field
     */
    public function take($take)
    {
        $this->take = $take;

        return $this;
    }

    /**
     * Render the layout, .i.e. put it in a control group or a different
     * wrapper
     *
     * @return string HTML
     */
    public function wrapAndRender()
    {
        // Don't set an id
        $this->setAttribute('id', false);

        // Because it's a field, Former will add this.  But it's not really
        // appropriate for a listing
        $this->removeClass('form-control');

        // Render the markup
        if ($this->layout == 'form') {
            return $this->wrapInControlGroup();
        }

        return $this->render();
    }

    /**
     * Add customization to the control group rendering
     *
     * @return string HTML
     */
    protected function wrapInControlGroup()
    {
        // Add generic stuff
        $this->addGroupClass('list-form-group');

        // Use the controller description for blockhelp
        if (!$this->hasHelp()) {
            $this->blockhelp($this->controller->description());
        }

        // Show no results if there is no parent specified
        if (empty($this->parent_item)) {
            $this->addGroupClass('note');

            return $this->group->wrapField(Former::note($this->label_text, trans('decoy::form.listing.pending_save', ['model' => $this->label_text, 'description' => $this->controller->description()])));
        }

        // Add create button if we have permission and if there is a parent item
        if (app('decoy.user')->can('create', $this->controller)) {
            $this->group->setLabel(
                '<a href="'.$this->getIndexURL().'">'
                .$this->label_text
                .'</a>'
                .$this->makeCreateBtn()
            );
        }

        // Return the wrapped field
        return $this->group->wrapField($this);
    }

    /**
     * Render the listing.  This is added using getContent so Former will wrap it all in a
     * div that classes (and other Former APIs) can by chained onto.
     *
     * @return string HTML
     */
    public function getContent()
    {
        // Check that the current user has permission to access this controller
        if (!app('decoy.user')->can('read', $this->controller)) {
            return;
        }

        // If in a sidebar and there is no parent (like if you are on a create page)
        // then don't show a special message
        if ($this->layout == 'sidebar' && !$this->parent_item) {
            return View::make('decoy::shared.list._pending', [
                'title' => $this->label_text,
                'description' => $this->controller->description(),
            ])->render();
        }

        // Get the listing of items
        $items = $this->getItems();

        // Get an instance of a model to call non-static methods on
        $model_class = $this->controller->model();
        $model_instance = new $model_class;

        // Create all the vars the standard list expects
        $vars = [
            'controller'        => $this->controller_name,
            'title'             => $this->label_text,
            'description'       => $this->controller->description(),
            'columns'           => $this->getColumns($this->controller),
            'search'            => $this->controller->search(),
            'convert_dates'     => 'date',
            'layout'            => $this->layout,
            'parent_id'         => null,
            'parent_controller' => null,
            'many_to_many'      => false,
            'listing'           => $items,
            'count'             => is_a($items, LengthAwarePaginator::class) ?
                $items->total() : $items->count(),
            'paginator_from'    => (request('page', 1)-1) * $this->perPage(),
            'with_trashed'      => $this->controller->withTrashed(),
            'is_exportable'     => $model_instance->isExportable(),
        ];

        // If the listing has a parent, add relationship vars
        if ($this->parent_item) {
            $vars = array_merge($vars, $this->controller->autocompleteViewVars());
        }

        // Return the view, passing in a bunch of variables
        return View::make('decoy::shared.list._standard', $vars)->render();
    }

    /**
     * Make the create button for control group listing
     *
     * @return string HTML
     */
    protected function makeCreateBtn()
    {
        return '<div class="btn-group">
            <a href="'.URL::to($this->getCreateURL()).'" class="btn btn-info btn-small new">
            <span class="glyphicon glyphicon-plus"></span> ' . __('decoy::form.listing.new') . '</a>
            </div>';
    }

    /**
     * Get the index URL for this controller
     *
     * @return string URL
     */
    protected function getIndexURL()
    {
        return $this->controller->isChildInManyToMany() ?
            DecoyURL::action($this->controller_name.'@index') :
            DecoyURL::relative('index', null, $this->controller_name);
    }

    /**
     * Get the create URL for this controller
     *
     * @return string URL
     */
    protected function getCreateURL()
    {
        return $this->controller->isChildInManyToMany() ?
            DecoyURL::action($this->controller_name.'@create') :
            DecoyURL::relative('create', null, $this->controller_name);
    }

    /**
     * Get the list of columns of the controller
     *
     * @param  Bkwld\Decoy\Controller\Base $controller A controller instance
     * @return array                       Associative array of column keys and values
     */
    protected function getColumns($controller)
    {
        // If user has defined columns, use them
        if ($this->columns) {
            return $this->columns;
        }

        // Read columns from the controller
        $columns = $controller->columns();

        // If showing in sidebar, only show the first column
        // http://stackoverflow.com/a/1028677/59160
        if ($this->layout == 'sidebar') {
            $val = reset($columns); // Making sure this gets called before `key()`
            return [key($columns) => $val];

        // Otherwise, just return all columns
        }

        return $columns;
    }

    /**
     * Create the list of items to display
     *
     * @return LengthAwarePaginator | Collection
     */
    public function getItems()
    {
        // The user specified items so use them
        if ($this->items) {
            return $this->items;
        }

        // Query the DB for items to display
        return $this->queryForItems();
    }

    /**
     * Write an execute a query to get the list of items
     *
     * @return LengthAwarePaginator
     */
    protected function queryForItems()
    {
        // If there is a parent, run the query through the relationship to this model
        // from the parent
        if ($this->parent_item) {
            $relationship = Decoy::hasManyName($this->name);
            $query = $this->parent_item->$relationship()->ordered();
        }

        // Otherwise, open up the query using ordered
        else {
            $query = call_user_func($this->name.'::ordered');
        }

        // Apply any custom scope
        if ($this->scope) {
            call_user_func($this->scope, $query);
        }

        // Retrieve the results through paginator
        return $query->paginate($this->perPage());
    }

    /**
     * Get the amount per page be looking at a number of different sources
     *
     * @return int The amount
     */
    protected function perPage()
    {
        // If the user specified a limit, use it
        if ($this->take) {
            return $this->take;
        }

        // If a sidebar, use the default
        if ($this->layout == 'sidebar') {
            $name = $this->controller_name;

            return $name::$per_sidebar;
        }

        // Else, use the controller's pagination logic
        return $this->controller->perPage();
    }
}
