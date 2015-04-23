<?php namespace Bkwld\Decoy\Controllers;

// Deps
use App;
use Bkwld\Decoy\Models\Admin;
use Input;
use Redirect;

/**
 * The CRUD listing of admins
 */
class Admins extends Base {

	/**
	 * Normal Decoy controller config.  There is some increased specifity so that
	 * subclassing controllers don't have to have to specify everything.
	 */
	protected $description = 'Users who have access to the admin.';
	protected $columns = array(
		'Name'          => 'getAdminTitleHtmlAttribute',
		'Status'        => 'getAdminStatusAttribute',
		'Email'         => 'email',
	);
	protected $show_view = 'decoy::admins.edit';

	/**
	 * If the user can't manage admins, bounce them to their profile page
	 * 
	 * @return Symfony\Component\HttpFoundation\Response | void
	 */
	public function index() {
		if (!app('decoy.auth')->can('manage', 'admins')) {
			return Redirect::to(app('decoy.auth')->userUrl());
		}
		return parent::index();
	}

	/**
	 * Make password optional
	 *
	 * @return void 
	 */
	public function edit($id) {
		unset(Admin::$rules['password']);
		return parent::edit($id);
	}

	/**
	 * Don't let unauthorize folks update their role by passing in role values
	 * in the GET
	 *
	 * @param  int $id Model key
	 * @return Symfony\Component\HttpFoundation\Response
	 */
	public function update($id) {

		// Encorce permissions on updating ones own role
		if (!app('decoy.auth')->can('manage', 'admins') && Input::has('role')) {
			return App::abort(401);
		}

		// If the password is empty, remove the key from the input so it isn't cleared
		if (!Input::has('password')) {
			Input::replace(array_except(Input::get(), ['password']));
		}

		// Continue processing
		return parent::update($id);
	}

	/**
	 * Disable the admin
	 * 
	 * @return Illuminate\Http\RedirectResponse
	 */
	public function disable($id) {
		if (!app('decoy.auth')->can('manage', 'admins')) return App::abort(401);
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
		if (!app('decoy.auth')->can('manage', 'admins')) return App::abort(401);
		if (!($admin = Admin::find($id))) return App::abort(404);
		$admin->active = 1;
		$admin->save();
		return Redirect::back();
	}

}