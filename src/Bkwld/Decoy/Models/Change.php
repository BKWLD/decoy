<?php namespace Bkwld\Decoy\Models;

// Deps\
use Bkwld\Decoy\Models\Admin;
use DB;
use Illuminate\Database\Eloquent\Model;

/**
 * Reperesents a single model change event.  Typically a single CRUD action on
 * a model.
 */
class Change extends Base {

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

}