<?php

namespace Bkwld\Decoy\Controllers;

/**
 * Allow admin to manage redirection rules
 */
class RedirectRules extends Base
{
    /**
     * @var string
     */
    protected $title = 'Redirects';

    /**
     * @var string
     */
    protected $description = 'Rules that redirect an internal URL path to another.';

    /**
     * @var array
     */
    protected $columns = [
        'Rule' => 'getAdminTitleAttribute',
    ];

    /**
     * @var array
     */
    protected $search = [
        'from',
        'to',
        'code' => [
            'type' => 'select',
            'options' => 'Bkwld\Decoy\Models\RedirectRule::$codes',
        ],
        'label',
    ];

    /**
     * Get the permission options.
     *
     * @return array An associative array.
     */
    public function getPermissionOptions()
    {
        return array_except(parent::getPermissionOptions(), ['publish']);
    }
}
