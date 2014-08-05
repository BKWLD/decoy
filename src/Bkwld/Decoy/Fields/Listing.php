<?php namespace Bkwld\Decoy\Fields;

// Dependencies
use Bkwld\Library;
use Bkwld\Decoy\Exception;
use Former\Traits\Field;
use HtmlObject\Input as HtmlInput;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Collection;
use DecoyURL;
use Former;
use Input;
use Str;
use View;
use URL;

/**
 * Create a table-layout listing of records in the database.  An index view.  It may
 * be presented in different styles.  Example:
 *
 * echo Former::listing('{model class}') // IE. 'Article'
 * 	->parent($item)
 * 	->layout('sidebar')
 * 	->scope(function($query) { return $query->where('category' ,'=', 'example') })
 * 	->take(20)
 */
class Listing extends Field {
	use Traits\CaptureLabel, Traits\Scopable;

	/**
	 * Override Former Field and Tag properties to wrap the listing inside of
	 * another div that can have classes and such added onto them.
	 */
	protected $element = 'div';
	protected $isSelfClosing = false;
	protected $injectedProperties = array();

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
	 * @var Illuminate\Pagination\Paginator | 
	 *      Illuminate\Database\Eloquent\Collection 
	 *      string $items
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
	 * @param string    $type       text
	 * @param string    $model      The model class name
	 * @param string    $label      Its label
	 * @param string    $value      Its value
	 * @param array     $attributes Attributes
   */
  public function __construct(Container $app, $type, $model, $label, $value, $attributes) {

  	// Instantiate a controller given the model name
  	$this->controller($this->controllerNameOfModel($model));

  	// If no title is passed, set it to the controller name
  	if (!$label && $this->controller) $label = $this->controller->title();
  	elseif (!$label) $label = Str::plural($model);

  	// Continue instantiation
    parent::__construct($app, $type, $model, $label, $value, $attributes);
  }

	/**
	 * Replace the controller
	 *
	 * @param  Bkwld\Decoy\Controllers\Base | string $controller
	 * @return Field This field
	 */
	public function controller($controller) {

		// Instantiate a string controller
		if (is_string($controller)
			&& class_exists($controller)
			&& is_subclass_of($controller, 'Bkwld\Decoy\Controllers\Base')) {
			$this->controller_name = $controller;
			$this->controller = new $controller;

		// Or, validate a passed controller instance
		} elseif (is_object($controller)
			&& is_a($controller, 'Bkwld\Decoy\Controllers\Base')) {
			$this->controller_name = get_class($controller);
			$this->controller = $controller;
		}

		// Re-apply the parent if one was set
		if ($this->parent_item && $this->controller) {
			$this->controller->parent($this->parent_item);
		}
		
		// Chain
		return $this;
	}

	/**
	 * Store the items that will be displayed in the list
	 *
	 * @param  Illuminate\Pagination\Paginator | 
	 *         Illuminate\Database\Eloquent\Collection 
	 *         string $items
	 * @return Field This field
	 */
	public function items($items) {
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
	 * @return Field This field
	 */
	public function layout($layout) {
		if ($layout == 'control group') $layout = 'form'; // Backwards compat
		if (in_array($layout, array('full', 'sidebar', 'form'))) $this->layout = $layout;
		return $this;
	}

	/**
	 * Store the parent model instance
	 *
	 * @param  Illuminate\Database\Eloquent\Model $parent
	 * @return Field This field
	 */
	public function parent($parent) {
		$this->parent_item = $parent;
		$this->controller->parent($parent);
		return $this;
	}

	/**
	 * Store the number of items to fetch, the per_page
	 *
	 * @param  int $take
	 * @return Field This field
	 */
	public function take($take) {
		$this->take = $take;
		return $this;
	}

	/**
	 * Render the layout, .i.e. put it in a control group or a different
	 * wrapper
	 *
	 * @return string HTML
	 */
	public function wrapAndRender() {
		if ($this->layout == 'form') return $this->wrapInControlGroup();
		return $this->render();
	}

	/**
	 * Add customization to the control group rendering
	 *
	 * @return string HTML
	 */
	protected function wrapInControlGroup() {

		// Add generic stuff
		$this->setAttribute('id', false);
		$this->addGroupClass('list-control-group');

		// Use the controller description for blockhelp
		$this->blockhelp($this->controller->description());

		// Show no results if there is no parent specified
		if (empty($this->parent_item)) {
			$this->addGroupClass('note');
			return $this->group->wrapField(Former::note($this->label_text, '
				<i class="icon-info-sign"></i> You must save before you can add <b>'.$this->label_text.'</b>.
			'));
		}

		// Add create button if we have permission and if there is a parent item
		if (app('decoy.auth')->can('create', $this->controller)) {
			$this->group->setLabel($this->label_text.$this->makeCreateBtn());
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
	public function getContent() {

		// Check that the current user has permission to access this controller
		if (!app('decoy.auth')->can('read', $this->controller)) continue;

		// Get the listing of items
		$items = $this->getItems();

		// Create all the vars the standard list expects
		$vars = array(
			'controller'        => $this->controller_name,
			'title'             => $this->label_text,
			'description'       => $this->controller->description(),
			'columns'           => $this->getColumns($this->controller),
			'auto_link'         => 'first',
			'convert_dates'     => 'date',
			'layout'            => $this->layout,
			'parent_id'         => null,
			'parent_controller' => null,
			'many_to_many'      => false,
			'tags'              => is_a($this->name, 'Bkwld\Decoy\Models\Tag'),
			'listing'           => $items,
			'count'             => is_a($items, 'Illuminate\Pagination\Paginator') ? $items->getTotal() : $items->count(),
			'paginator_from'    => (Input::get('page', 1)-1) * $this->perPage(),
		);

		// If parent, add relationship ones
		if ($this->parent_item) {
			$vars['parent_id'] = $this->parent_item->getKey();
			$vars['parent_controller'] = $this->controllerNameOfModel(get_class($this->parent_item));
			$vars['many_to_many'] = $this->controller->isChildInManyToMany();
		}

		// Return the view, passing in a bunch of variables
		return View::make('decoy::shared.list._standard', $vars)->render();

	}

	/**
	 * Make the controller name given the model
	 *
	 * @param string $model The name of a model class
	 * @return string
	 */
	protected function controllerNameOfModel($model) {
		return 'Admin\\'.Str::plural($model).'Controller';
	}


	/**
	 * Make the create button for control group listing
	 *
	 * @return string HTML
	 */
	protected function makeCreateBtn() {
		return '<div class="btn-group">
			<a href="'.URL::to($this->getCreateURL()).'" class="btn btn-info btn-small new">
			<i class="icon-plus icon-white"></i> New</a>
			</div>';
	}

	/**
	 * Get the create URL for this controller
	 *
	 * @return string URL
	 */
	protected function getCreateURL() {
		return $this->controller->isChildInManyToMany() ? 
			DecoyURL::action($this->controller_name.'@create') : 
			DecoyURL::relative('create', null, $this->controller_name);
	}

	/**
	 * Get the list of columns of the controller
	 *
	 * @param Bkwld\Decoy\Controller\Base $controller A controller instance
	 * @return  array Associative array of column keys and values
	 */
	protected function getColumns($controller) {

		// If user has defined columns, use them
		if ($this->columns) return $this->columns;

		// Read columns from the controller
		$columns = $controller->columns();

		// If showing in sidebar, only show the first column
		// http://stackoverflow.com/a/1028677/59160
		if ($this->layout == 'sidebar') {
			$val = reset($columns); // Making sure this gets called before `key()`
			return array(key($columns) => $val);

		// Otherwise, just return all columns
		} else return $columns;
	}

	/**
	 * Create the list of items to display
	 *
	 * @return Illuminate\Pagination\Paginator | Illuminate\Database\Eloquent\Collection
	 */
	protected function getItems() {

		// The user specified items so use them
		if ($this->items) return $this->items;

		// Query the DB for items to display
		else return $this->queryForItems();
	}

	/**
	 * Write an execute a query to get the list of items
	 *
	 * @return Illuminate\Pagination\Paginator
	 */
	protected function queryForItems() {

		// If there is a parent, run the query through the relationship to this model
		// from the parent
		if ($this->parent_item) {
			$relationship = lcfirst(Str::plural($this->name));
			$query = $this->parent_item->$relationship()->ordered();
		}

		// Otherwise, open up the query using ordered
		else $query = call_user_func($this->name.'::ordered');

		// Apply any custom scope
		if ($this->scope) call_user_func($this->scope, $query);

		// Retrieve the results through paginator
		return $query->paginate($this->perPage());
	}

	/**
	 * Get the amount per page be looking at a number of different sources
	 *
	 * @return int The amount
	 */
	protected function perPage() {

		// If the user specified a limit, use it
		if ($this->take) return $this->take;
		
		// If a sidebar, use the default
		else if ($this->layout == 'sidebar') {
			$name = $this->controller_name;
			return $name::$per_page_sidebar;
		}

		// Else, use the controller's pagination logic
		else return $this->controller->perPage();
	}

}