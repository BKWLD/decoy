<?php namespace Bkwld\Decoy\Controllers;

// Deps
use App;
use Bkwld\Decoy\Models\Admin;
use Redirect;

/**
 * The CRUD listing of admins
 */
class Admins extends Base {

	/**
	 * Normal Decoy controller config
	 */
	protected $description = 'Users who have access to the admin.';
	protected $columns = array(
		'Name'          => 'title',
		'Status'        => 'statuses',
		'Email'         => 'email',
	);

	/**
	 * Make the password optional
	 *
	 * @return void 
	 */
	public function edit($id) {
		unset(Model::$rules['password']);
		parent::edit($id);
	}

	/**
	 * Disable the admin
	 * 
	 * @return Illuminate\Http\RedirectResponse
	 */
	public function disable($id) {
		if (!($admin = Admin::find($id))) return App::abort(404);
		$admin->active = null;
		$admin->save();
		return Redirect::back();
	}
	
	/**
	 * Enable the admin
	 *
	 * @return Illuminate\Http\RedirectResponse
	 */
	public function enable($id) {
		if (!($admin = Admin::find($id))) return App::abort(404);
		$admin->active = 1;
		$admin->save();
		return Redirect::back();
	}

}