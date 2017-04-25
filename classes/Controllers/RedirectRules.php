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
            'options' => 'Bkwld\Decoy\Models\RedirectRule::getCodes()',
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

    /**
     * Populate protected properties on init
     */
    public function __construct()
    {
        $this->title = __('decoy::redirect_rules.controller.title');
        $this->description = __('decoy::redirect_rules.controller.description');
        $this->columns = [
            __('decoy::redirect_rules.controller.column.rule') => 'getAdminTitleAttribute',
        ];
        $this->search = [
            'from' => [
                'label' => __('decoy::redirect_rules.controller.search.from'),
                'type' => 'text',
            ],
            'to' => [
                'label' => __('decoy::redirect_rules.controller.search.to'),
                'type' => 'text',
            ],
            'code' => [
                'label' => __('decoy::redirect_rules.controller.search.code'),
                'type' => 'select',
                'options' => 'Bkwld\Decoy\Models\RedirectRule::getCodes()',
            ],
            'label' => [
                'label' => __('decoy::redirect_rules.controller.search.label'),
                'type' => 'text',
            ],
        ];

        parent::__construct();
    }
}
