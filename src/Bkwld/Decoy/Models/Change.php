<?php namespace Bkwld\Decoy\Models;

// Deps
use Bkwld\Decoy\Input\Search;
use Bkwld\Decoy\Models\Admin;
use Bkwld\Library\Utils\String;
use Config;
use DB;
use DecoyURL;
use Illuminate\Database\Eloquent\Model;
use Str;

/**
 * Reperesents a single model change event.  Typically a single CRUD action on
 * a model.
 */
class Change extends Base {

	/**
	 * Always eager load the admins
	 *
	 * @var array
	 */
	protected $with = ['admin'];

	/**
	 * List of all relationships
	 *
	 * @return Illuminate\Database\Eloquent\Relations\Relation
	 */
	public function admin() { return $this->belongsTo('Bkwld\Decoy\Models\Admin'); }
	public function loggable() { return $this->morphTo(); }

	/**
	 * Check whether changes are enabled
	 *
	 * @return boolean
	 */
	public static function enabled() {
		if ($check = Config::get('decoy::site.log_changes')) {
			if (is_bool($check)) return $check;
			if (is_callable($check)) return call_user_func($check, $model, $action, app('decoy.auth')->user());
		}
		return false;
	}

	/**
	 * A convenience method for saving a change instance
	 *
	 * @param Model  $model The model being touched
	 * @param string $action Generally a CRUD verb: "created", "updated", "deleted"
	 * @param Admin  $admin The admin acting on the record
	 * @return static
	 */
	public static function log(Model $model, $action, Admin $admin) {

		// Create a new change instance
		$change = static::create([
			'model' => get_class($model),
			'key' => $model->getKey(),
			'action' => $action,
			'title' => method_exists($model, 'getAdminTitleAttribute') ? $model->getAdminTitleAttribute() : null,
			'changed' => $action != 'deleted' ? json_encode($model->getDirty()) : null,
			'admin_id' => $admin->getKey(),
		]);

		// If the action was a deletion, mark all of the records for this model as
		// deleted
		if ($action == 'deleted') {
			DB::table('changes')
				->where('model', get_class($model))
				->where('key', $model->getKey())
				->update(['deleted' => 1])
			;
		}
		
		// Return the changed instance
		return $change;
	}

	/**
	 * Return a list of all the actions currently being used as a hash for use
	 * in a select menu
	 *
	 * @return array 
	 */
	static public function getActions() {
		return static::groupBy('action')->lists('action', 'action');
	}

	/**
	 * Return a list of all the admins that have been logged as a hash for use
	 * in a select menu
	 *
	 * @return array 
	 */
	static public function getAdmins() {
		return static::groupBy('admin_id')
		->join('admins', 'admins.id', '=', 'admin_id')
		->select(DB::raw('changes.id, CONCAT(first_name, " ", last_name) name'))
		->lists('name', 'id');
	}

	/**
	 * Format the the activity like a sentance
	 *
	 * @return string HTML
	 */
	public function getAdminTitleHtmlAttribute() {
		return $this->getAdminLinkAttribute()
			.' '.$this->getActionLabelAttribute()
			.' the '.$this->getModelAttribute()
			.' "'.$this->getModelTitleAttribute().'"'
			.' about '.$this->getDateAttribute()
		;
	}

	/**
	 * Get the admin name and link
	 *
	 * @return string HTML
	 */
	public function getAdminLinkAttribute() {
		return sprintf('<a href="%s">%s</a>',
			$this->filterUrl(['admin_id' => $this->admin_id]),
			$this->admin->getAdminTitleHtmlAttribute());
	}

	/**
	 * Format the activity as a colored label
	 *
	 * @return string HTML
	 */
	public function getActionLabelAttribute() {
		$map = [
			'created' => 'success',
			'updated' => 'warning',
			'deleted' => 'danger',
		];
		return sprintf('<a href="%s" class="label label-%s">%s</a>',
			$this->filterUrl(['action' => $this->action]),
			isset($map[$this->action]) ? $map[$this->action] : 'info',
			$this->action);
	}

	/**
	 * Format the model name by translating it through the contorller's defined
	 * title
	 *
	 * @return string HTML
	 */
	public function getModelAttribute() {
		$controller = call_user_func($this->model.'::adminControllerClass');
		$controller = new $controller;
		return sprintf('<b class="js-tooltip" title="%s"><a href="%s">%s</a></b>',
			$controller->description(),
			$this->filterUrl(['model' => $this->model]),
			Str::singular($controller->title()));
	}

	/**
	 * Get the title of the model. Perhaps in the future there will be more smarts
	 * here, like generating a link to the edit view
	 *
	 * @return string HTML
	 */
	public function getModelTitleAttribute() {
		return sprintf('<a href="%s">%s</a>',
			$this->filterUrl(['model' => $this->model, 'key' => $this->key]),
			$this->title);
	}

	/**
	 * Get the date of the change
	 *
	 * @return string HTML
	 */
	public function getDateAttribute() {
		return sprintf('<a href="%s" class="js-tooltip" title="%s">%s</a>',
			$this->filterUrl(['created_at' => $this->created_at->format('m/d/Y')]),
			$this->getHumanDateAttribute(),
			$this->created_at->diffForHumans());
	}

	/**
	 * Get the human readable date
	 *
	 * @return string 
	 */
	public function getHumanDateAttribute() {
		return $this->created_at->format('M j, Y \a\t g:i A');
	}

	/**
	 * Customize the action links
	 *
	 * @param array $data The data passed to a listing view
	 * @return array 
	 */
	public function makeAdminActions($data) {
		$actions = [];

		// Always add a filter icon
		$actions[] = sprintf('<a href="%s" 
			class="glyphicon glyphicon-filter js-tooltip" 
			title="Filter to just changes of this <b>%s</b>" 
			data-placement="left"></a>',
			$this->filterUrl(['model' => $this->model, 'key' => $this->key]),
			$this->model);

		// If there are changes, add the modal button
		if ($this->changed) $actions[] = sprintf('<a href="%s" 
			class="glyphicon glyphicon-export js-tooltip changes-modal-link" 
			title="View changed attributes" 
			data-placement="left"></a>',
			DecoyURL::action('changes', $this->id));

		// Else, show a disabled bitton
		else $actions[] = '<span class="glyphicon glyphicon-export js-tooltip"
			title="Content was deleted"
			data-placement="left"></span>';

		// Return the actions
		return $actions;
	}

	/**
	 * Make a link to filter the result set
	 *
	 * @return string 
	 */
	public function filterUrl($query) {
		return DecoyURL::action('changes').'?'.Search::query($query);
	}

	/**
	 * Get just the attributes that should be displayed in the admin modal.
	 *
	 * @return array 
	 */
	public function attributesForModal() {
		return array_flip(
			array_map(function($key) { return String::titleFromKey($key); }, // Convert keys to labels
				array_flip(
					array_filter( // Strip nulled attributes
						array_except( // Remove some of them
							json_decode($this->changed, true), // Get the changed attribtues
							['id', 'updated_at', 'created_at', 'password', 'remember_token']
		)))));
	}

}