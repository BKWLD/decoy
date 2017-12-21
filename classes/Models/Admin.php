<?php

namespace Bkwld\Decoy\Models;

// Deps
use URL;
use HTML;
use Mail;
use Decoy;
use Config;
use Request;
use DecoyURL;
use Bkwld\Library\Utils\Text;
use Bkwld\Decoy\Auth\AuthInterface;
use Bkwld\Decoy\Notifications\ResetPassword;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Notifications\Notifiable;

class Admin extends Base implements
    AuthInterface,
    AuthenticatableContract,
    CanResetPasswordContract
{
    // Note, not using the Authorizable trait because I've defined my own versions
    // for backwards compatability with Decoy 4 and also to have a tigher syntax.
    use Authenticatable, CanResetPassword, Traits\HasImages, Notifiable;

    /**
     * The table associated with the model.  Explicitly declaring so that sub
     * classes can use it
     *
     * @var string
     */
    protected $table = 'admins';

    /**
     * Don't allow cloning because duplicate emails are not allowed.
     *
     * @var boolean
     */
    public $cloneable = false;

    /**
     * Admins should not be localized
     *
     * @var boolean
     */
    public static $localizable = false;

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'first_name' => 'required',
        'last_name' => 'required',
        'images.default' => 'image',
        'email' => 'required|email|unique:admins,email',
        'password' => 'required',
        'confirm_password' => 'sometimes|required_with:password|same:password',
    ];

    /**
     * Orders instances of this model in the admin
     *
     * @param  Illuminate\Database\Query\Builder $query
     * @return void
     */
    public function scopeOrdered($query)
    {
        $query->orderBy('last_name')->orderBy('first_name');
    }

    /**
     * Tweak some validation rules
     *
     * @param Illuminate\Validation\Validator $validation
     */
    public function onValidating($validation)
    {
        // Only apply mods when editing an existing record
        if (!$this->exists) {
            return;
        }

        $rules = self::$rules;

        // Make password optional
        $rules = array_except($rules, 'password');

        // Ignore the current record when validating email
        $rules['email'] .= ','.$this->id;

        // Update rules
        $validation->setRules($rules);
    }

    /**
     * New admin callbacks
     *
     * @return void
     */
    public function onCreating()
    {
        // Send out email
        if (Request::has('_send_email')) {
            $this->sendCreateEmail();
        }

        // Make them active
        $this->active = 1;

        // If the current user can't grant permissions, make the new admin
        // have the same role as themselves.  Admins created from the CLI (like as
        // part of a migration) won't be logged in.
        if (($admin = app('decoy.user'))
            && !app('decoy.user')->can('grant', 'admins')) {
            $this->role = $admin->role;

        // Otherwise, give the admin a default role if none was defined
        } elseif (empty($this->role)) {
            $this->role = 'admin';
        }
    }

    /**
     * Admin updating callbacks
     *
     * @return void
     */
    public function onUpdating()
    {
        if (Request::has('_send_email')) {
            $this->sendUpdateEmail();
        }
    }

    /**
     * Callbacks regardless of new or old
     *
     * @return void
     */
    public function onSaving()
    {
        // If the password is changing, hash it
        if ($this->isDirty('password')) {
            $this->password = bcrypt($this->password);
        }

        // Save or clear permission choices if the form had a "custom permissions"
        // pushed checkbox
        if (Request::exists('_custom_permissions')) {
            $this->permissions = request('_custom_permissions') ?
                json_encode(request('_permission')) : null;
        }
    }

    /**
     * Send creation email
     *
     * @return void
     */
    public function sendCreateEmail()
    {
        // Prepare data for mail
        $admin = app('decoy.user');
        $email = [
            'first_name' => $admin->first_name,
            'last_name' => $admin->last_name,
            'email' => request('email'),
            'url' => Request::root().'/'.Config::get('decoy.core.dir'),
            'root' => Request::root(),
            'password' => request('password'),
        ];

        // Send the email
        Mail::send('decoy::emails.create', $email, function ($m) use ($email) {
            $m->to($email['email'], $email['first_name'].' '.$email['last_name']);
            $m->subject('Welcome to the '.Decoy::site().' admin site');
        });
    }

    /**
     * Send update email
     *
     * @return void
     */
    public function sendUpdateEmail()
    {
        // Prepare data for mail
        $admin = app('decoy.user');
        $email = [
            'editor_first_name' => $admin->first_name,
            'editor_last_name' => $admin->last_name,
            'first_name' =>request('first_name'),
            'last_name' =>request('last_name'),
            'email' => request('email'),
            'password' =>request('password'),
            'url' => Request::root().'/'.Config::get('decoy.core.dir'),
            'root' => Request::root(),
        ];

        // Send the email
        Mail::send('decoy::emails.update', $email, function ($m) use ($email) {
            $m->to($email['email'], $email['first_name'].' '.$email['last_name']);
            $m->subject('Your '.Decoy::site().' admin account info has been updated');
        });
    }

    /**
     * Determine if the entity has a given ability.
     *
     * @param  string $action
     * @param  string $controller
     * @return bool
     */
    public function can($action, $controller)
    {
        return app(Gate::class)
            ->forUser($this)
            ->check('decoy.auth', [$action, $controller]);
    }

    /**
     * Determine if the entity does not have a given ability.
     *
     * @param  string $action
     * @param  string $controller
     * @return bool
     */
    public function cant($action, $controller)
    {
        return !$this->can($action, $controller);
    }

    /**
     * Determine if the entity does not have a given ability.
     *
     * @param  string $action
     * @param  string $controller
     * @return bool
     */
    public function cannot($action, $controller)
    {
        return $this->cant($action, $controller);
    }

    /**
     * Don't log if admin is logging in and out
     *
     * @param  string $action
     * @return boolean
     */
    public function shouldLogChange($action)
    {
        if ($action != 'deleted'
            && count($this->getDirty()) == 1
            && $this->isDirty('remember_token')) {
            return false;
        }
        return parent::shouldLogChange($action);
    }

    /**
     * Send the password reset notification. This overrides a method inheritted
     * from the CanResetPassword trait
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPassword($token));
    }

    /**
     * A shorthand for getting the admin name as a string
     *
     * @return string
     */
    public function getNameAttribute()
    {
        return $this->getAdminTitleAttribute();
    }

    /**
     * Produce the title for the list view
     *
     * @return string
     */
    public function getAdminTitleHtmlAttribute()
    {
        if ($this->getAdminThumbTagAttribute()) {
            return parent::getAdminTitleHtmlAttribute();
        }

        return "<img src='".$this->getGravatarAttribute()."' class='gravatar'/> "
            .$this->getAdminTitleAttribute();
    }

    /**
     * Return the gravatar URL for the admin
     */
    public function getGravatarAttribute()
    {
        return '//www.gravatar.com/avatar/'.md5(strtolower(trim($this->email)));
    }

    /**
     * Show a badge if the user is the currently logged in
     *
     * @return string
     */
    public function getAdminStatusAttribute()
    {
        $html ='';

        // Add the role
        if (($roles = static::getRoleTitles()) && count($roles)) {
            $html .= '<span class="label label-primary">'.$roles[$this->role].'</span>';
        }

        // If row is you
        if ($this->id == app('decoy.user')->id) {
            $html .= '<span class="label label-info">' . __('decoy::admins.standard_list.you') . '</span>';
        }

        // If row is disabled
        if ($this->disabled()) {
            $html .= '<a href="' . URL::to(DecoyURL::relative('enable', $this->id)) . '" class="label label-warning
                js-tooltip" title="' . __('decoy::admins.standard_list.click') . '">' .
                __('decoy::admins.standard_list.disabled') . '</a>';
        }

        // Return HTML
        return $html;
    }

    /**
     * Get the URL to edit the admin
     *
     * @return string
     */
    public function getAdminEditAttribute()
    {
        return DecoyURL::action('Bkwld\Decoy\Controllers\Admins@edit', $this->id);
    }

    /**
     * Get the permissions for the admin
     *
     * @return stdObject
     */
    public function getPermissionsAttribute()
    {
        if (empty($this->permissions)) {
            return null;
        }

        return json_decode($this->permissions);
    }

    /**
     * Make a list of the role titles by getting just the text between bold tags
     * in the roles config array, which is a common convention in Decoy
     *
     * @return array
     */
    public static function getRoleTitles()
    {
        return array_map(function ($title) {
            if (preg_match('#^<b>([^<]+)</b>#i', $title, $matches)) {
                return $matches[1];
            }

            return $title;
        }, config('decoy.site.roles'));
    }

    /**
     * Get the list of all permissions
     *
     * @param  Admin|null $admin
     * @return array
     */
    public static function getPermissionOptions($admin = null)
    {
        // Get all the app controllers
        $controllers = array_map(function($path) {
            return 'App\Http\Controllers\Admin\\'.basename($path, '.php');
        }, glob(app_path('/Http/Controllers/Admin/*.php')));

        // Add some Decoy controllers
        $controllers[] = 'Bkwld\Decoy\Controllers\Admins';
        $controllers[] = 'Bkwld\Decoy\Controllers\Changes';
        $controllers[] = 'Bkwld\Decoy\Controllers\Elements';
        $controllers[] = 'Bkwld\Decoy\Controllers\RedirectRules';

        // Alphabetize the controller classes
        usort($controllers, function ($a, $b) {
            return substr($a, strrpos($a, '\\') + 1) > substr($b, strrpos($b, '\\') + 1);
        });

        // Convert the list of controller classes into the shorthand strings used
        // by Decoy Auth as well as english name and desciption
        return array_map(function ($class) use ($admin) {
            $obj = new $class;
            $permissions = $obj->getPermissionOptions();
            if (!is_array($permissions)) {
                $permissions = [];
            }

            // Build the controller-level node
            return (object) [

                // Add controller information
                'slug' => DecoyURL::slugController($class),
                'title' => $obj->title(),
                'description' => $obj->description(),

                // Add permission options for the controller
                'permissions' => array_map(function ($value, $action) use ($class, $admin) {
                    $roles = array_keys(Config::get('decoy.site.roles'));

                    return (object) [
                        'slug' => $action,
                        'title' => is_array($value) ? $value[0] : Text::titleFromKey($action),
                        'description' => is_array($value) ? $value[1] : $value,

                        // Set the initial checked state based on the admin's permissions, if
                        // one is set.  Or based on the first role.
                        'checked' => $admin ?
                            $admin->can($action, $class) :
                            with(new Admin(['role' => $roles[0]]))->can($action, $class),

                        // Filter the list of roles to just the roles that allow the
                        // permission currently being iterated through
                        'roles' => array_filter($roles, function ($role) use ($action, $class) {
                            return with(new Admin(['role' => $role]))->can($action, $class);
                        }),

                    ];
                }, $permissions, array_keys($permissions)),
            ];
        }, $controllers);
    }

    /**
     * Check if admin is banned
     *
     * @return boolean true if banned
     */
    public function disabled()
    {
        return !$this->active;
    }

    /**
     * Check if a developer
     *
     * @return boolean
     */
    public function isDeveloper()
    {
        return $this->role == 'developer' || strpos($this->email, 'bkwld.com');
    }

    /**
     * Avatar photo for the header
     *
     * @return string
     */
    public function getUserPhoto()
    {
        return $this->getAdminThumbAttribute(80, 80) ?: $this->getGravatarAttribute();
    }

    /**
     * Name to display in the header for the user
     *
     * @return string
     */
    public function getShortName()
    {
        return $this->first_name;
    }

    /**
     * URL to the user's profile page in the admin
     *
     * @return string
     */
    public function getUserUrl()
    {
        return $this->getAdminEditAttribute();
    }
}
