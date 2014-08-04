<?php namespace Bkwld\Decoy\Fields;

// Dependencies
use Bkwld\Library;
use Bkwld\Decoy\Exception;
use Former\Traits\Field;
use HtmlObject\Input as HtmlInput;
use Illuminate\Container\Container;
use Input;
use Str;
use View;

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
	use Traits\CaptureLabel;
	use Traits\Scopable;

	/**
	 * Preserve the controller
	 *
	 * @var Bkwld\Decoy\Controller\Base | string
	 */
	private $controller;

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
	 * Store the controller class name or instance
	 *
	 * @param  Illuminate\Pagination\Paginator | array $controller
	 * @return Field This field
	 */
	public function controller($controller) {
		$this->controller = $controller;
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
	 * - control group
	 *
	 * @param  string $layout
	 * @return Field This field
	 */
	public function layout($layout) {
		if (in_array($layout, array(
			'full', 
			'sidebar', 
			'control group',
			))) $this->layout = $layout;
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
	 * @return string
	 */
	public function wrapAndRender() {
		return $this->render();
	}

	/**
	 * Render the listing
	 *
	 * @return string An input tag
	 */
	public function render() {

		// Get a controller instance
		$controller = $this->createController();
		$controller_name = get_class($controller);

		// Check that the current user has permission to access this controller
		if (!app('decoy.auth')->can('read', $controller_name)) continue;

		// Get the listing of items
		$items = $this->getItems();

		// Create all the vars the standard list expects
		$vars = array(
			'controller'        => $controller_name,
			'title'             => $this->label_text ?: $controller->title(),
			'description'       => $controller->description(),
			'columns'           => $this->getColumns($controller),
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
			$vars['many_to_many'] = $controller->isChildInManyToMany();
		}

		// Return the view, passing in a bunch of variables
		return View::make('decoy::shared.list._standard', $vars)->render();

	}

	/**
	 * Create an instance of the controller being listed
	 *
	 * @return Bkwld\Decoy\Controller\Base
	 */
	protected function createController() {
		if (empty($this->controller)) return $this->createControllerFromModel();
		else if (is_string($this->controller)) return new $this->controller;
		else if (is_a($this->controller, 'Bkwld\Decoy\Controller\Base')) return $this->controller;
		throw new Exception('Could not create a controller instance');
	}

	/**
	 * Guess at the name of the controller given it's model and instantiate it
	 *
	 * @return Bkwld\Decoy\Controller\Base
	 */
	protected function createControllerFromModel() {
		$controller = $this->controllerNameOfModel($this->name);
		return new $controller;
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
		if ($this->items) return $this->items;
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
		if ($this->take) return $this->take;
		$controller = $this->controllerNameOfModel($this->name);
		if ($this->layout == 'sidebar') return $controller::$per_page_sidebar;
		else return Input::get('count', $controller::$per_page);
	}

}