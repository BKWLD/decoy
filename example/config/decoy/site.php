<?php return array(

    /**
     * The name of the site is shown in the header of all pages
     *
     * @var string
     */
    'name' => config('site.name') ? config('site.name') : 'Admin',

    /**
     * This generates the nav sidebar.  It is key-value pairs.  The key is always the
     * label for the nav item.  The value may be either an array of label-url pairs or
     * a string URL.  The URL to an index view will use slugs that are the plural of
     * the model name.  For instance, the Article model can be found at /admin/articles
     *
     * In addition, you may append an icon name after a label, delimited by
     * a comma.  The icon name should be the suffix of a Bootstrap Glyphicon.  For example,
     * to show the `glyphicon-book` icon, just append `,book` to the end of the label.
     *
     * @var callable|array
     */
    'nav' => [
        'Content,book' => [
            'Articles' => '/admin/articles',
            'Tags' => '/admin/tags',
        ],
        'Recipes,grain' => '/admin/recipes',
        'Elements,leaf' => '/admin/elements',
        'Redirects,new-window' => '/admin/redirect-rules',
    ],

    /**
     * After a succesful login, this is the absolute path or url that should be
     * redirected to.  Make falsey to redirect to the first page in the nav
     *
     * @var callable|string|null
     */
    // 'post_login_redirect' => '/admin/admins',

    /**
     * Roles that super admins can assign other admins to on the admin edit page.
     * If left empty, all admins will be assigned to the default level of "admin".
     *
     * @var array
     */
    'roles' => [
        'admin' => '<b>Super admin</b> - Can manage all content.',
        'viewer' => '<b>Viewer</b> - Can only read.',
    ],

    /**
     * Permissions rules.  These are described in more detail in the README.
     *
     * @var array
     */
    'permissions' => [
        'viewer' => [
            'can' => [
                'read.articles',
                'read.admins',
                'manage.tags',
            ],
            'cant' => [
                'destroy.tags',
            ]
        ],
    ],

    /**
     * A hash of localization slugs and readable labels for all the locales for this
     * site.  Localization UI will only appear if the count > 1.
     *
     * @var array
     */
    'locales' => [
        'en' => 'English',
        'es' => 'Spanish',
        'fr' => 'French',
    ],

    /**
     * Automatically apply localization options to all models that at the root
     * level in the nav.  The thinking is that a site that is localized should
     * have everything localized but that children will inherit the localization
     * preference from a parent.
     *
     * @var boolean
     */
    'auto_localize_root_models' => false,

    /**
     * Store an entry in the database of all model changes.
     *
     * @var boolean|callable
     *
     * If a function, it's signature is:
     *
     * @param Illuminate\Database\Eloquent\Model $model The model being touched
     * @param string $action Generally a CRUD verb: "created", "updated", "deleted"
     * @param Bkwld\Decoy\Models\Admin $admin The admin acting on the record
     * @return boolean
     */
    // 'log_changes' => true,
    'log_changes' => function($model, $action, $admin_id) {
        return true;
    },

);
