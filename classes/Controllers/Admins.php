<?php

namespace Bkwld\Decoy\Controllers;

use App;
use Request;
use Redirect;
use Bkwld\Decoy\Models\Admin;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * The CRUD listing of admins
 */
class Admins extends Base
{
    /**
     * @var string
     */
    protected $show_view = 'decoy::admins.edit';

    /**
     * Make search options dependent on whether the site is using roles
     *
     * @return array
     */
    public function search()
    {
        $options = [
            'first_name' => [
                'label' => __('decoy::admins.controller.search.first_name'),
                'type' => 'text',
            ],
            'last_name' => [
                'label' => __('decoy::admins.controller.search.last_name'),
                'type' => 'text',
            ],
            'email' => [
                'label' => __('decoy::admins.controller.search.email'),
                'type' => 'text',
            ],
            'status' => [
                'label' => __('decoy::admins.controller.search.status'),
                'type' => 'select',
                'options' => [
                    1 => __('decoy::admins.controller.search.enabled'),
                    0 => __('decoy::admins.controller.search.disabled'),
                ],
            ],
        ];

        if (($roles = Admin::getRoleTitles()) && count($roles)) {
            $options['role'] = [
                'label' => __('decoy::admins.controller.search.role'),
                'type' => 'select',
                'options' => $roles,
            ];
        }

        return $options;
    }

    /**
     * Add a "grant" option for assigning permissions and disabling folks
     *
     * @return array
     */
    public function getPermissionOptions()
    {
        return [
            'read' => 'View listing and edit views',
            'create' => 'Create new items',
            'update' => 'Update existing items',
            'grant' => 'Change role and permissions',
            'destroy' => ['Delete', 'Delete items permanently'],
        ];
    }

    /**
     * If the user can't read admins, bounce them to their profile page
     *
     * @return Symfony\Component\HttpFoundation\Response|void
     */
    public function index()
    {
        if (!app('decoy.user')->can('read', 'admins')) {
            return Redirect::to(app('decoy.user')->getUserUrl());
        }
        return parent::index();
    }

    /**
     * Make password optional
     *
     * @return void
     */
    public function edit($id)
    {
        unset(Admin::$rules['password']);
        return parent::edit($id);
    }

    /**
     * Don't let unauthorize folks update their role by passing in role values
     * in the GET
     *
     * @param  int                                       $id Model key
     * @throws AccessDeniedHttpException
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function update($id)
    {
        // Encorce permissions on updating ones own role
        if (!app('decoy.user')->can('update', 'admins') && Request::has('role')) {
            throw new AccessDeniedHttpException;
        }

        // If the password is empty, remove the key from the input so it isn't cleared
        if (!Request::has('password')) {
            Request::replace(array_except(Request::input(), ['password']));
        }

        // Continue processing
        return parent::update($id);
    }

    /**
     * Disable the admin
     *
     * @return Illuminate\Http\RedirectResponse
     */
    public function disable($id)
    {
        if (!app('decoy.user')->can('grant', 'admins')) {
            throw new AccessDeniedHttpException;
        }

        if (!($admin = Admin::find($id))) {
            return App::abort(404);
        }

        $admin->active = null;
        $admin->save();

        return Redirect::back();
    }

    /**
     * Enable the admin
     *
     * @return Illuminate\Http\RedirectResponse
     */
    public function enable($id)
    {
        if (!app('decoy.user')->can('grant', 'admins')) {
            throw new AccessDeniedHttpException;
        }

        if (!($admin = Admin::find($id))) {
            return App::abort(404);
        }

        $admin->active = 1;
        $admin->save();

        return Redirect::back();
    }

    /**
     * Populate protected properties with localized labels on init
     *
     * @return $this
     */
    public function __construct()
    {
        $this->title = __('decoy::admins.controller.title');
        $this->description = __('decoy::admins.controller.description');
        $this->columns = [
            __('decoy::admins.controller.column.name') => 'getAdminTitleHtmlAttribute',
            __('decoy::admins.controller.column.status') => 'getAdminStatusAttribute',
            __('decoy::admins.controller.column.email') => 'email',
        ];

        parent::__construct();
    }
}
