<?php namespace Bkwld\Decoy\Models;

// Deps
use Bkwld\Decoy\Models\Admin;
use DB;
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
			.' about '.$this->getHumanDateAttribute()
		;
	}

	/**
	 * Get the admin name and link
	 *
	 * @return string HTML
	 */
	public function getAdminLinkAttribute() {
		return '<a href="'.$this->admin->getAdminEditAttribute().'">'
			.$this->admin->getAdminTitleHtmlAttribute().'</a>';
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
		$type = @$map[$this->action] ?: 'info';
		return "<span class='label label-{$type}'>{$this->action}</span>";
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
		return '<b class="js-tooltip" title="'.$controller->description().'">'
			.Str::singular($controller->title()).'</b>';
	}

	/**
	 * Get the title of the model. Perhaps in the future there will be more smarts
	 * here, like generating a link to the edit view
	 *
	 * @return string HTML
	 */
	public function getModelTitleAttribute() {
		return $this->title;
	}

	/**
	 * Get the date of the change
	 *
	 * @return string HTML
	 */
	public function getHumanDateAttribute() {
		return '<span class="js-tooltip" title="'
			.$this->created_at->toDayDateTimeString().'">'
			.$this->created_at->diffForHumans().'</span>';
	}

}