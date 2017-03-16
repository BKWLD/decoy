<?php

namespace Bkwld\Decoy\Controllers;

use Response;
use Bkwld\Decoy\Models\Change;

/**
 * A log of model changes, used for auditing Admin activity. Can also be used
 * as a source for recovering changed / deleted content.
 */
class Changes extends Base
{
    /**
     * @var string
     */
    protected $description = 'A log of actions that can be used to audit <b>Admin</b> activity or recover content.';

    /**
     * @var array
     */
    protected $columns = [
        'Activity' => 'getAdminTitleHtmlAttribute',
    ];

    /**
     * @var array
     */
    protected $search = [
        'model' => [
            'type' => 'text',
            'label' => 'Type',
        ],
        'key',
        'action' => [
            'type' => 'select',
            'options' => 'Bkwld\Decoy\Models\Change::getActions()',
        ],
        'title',
        'admin_id' => [
            'type' => 'select',
            'label' => 'Admin',
            'options' => 'Bkwld\Decoy\Models\Change::getAdmins()',
        ],
        'created_at' => [
            'type' => 'date',
            'label' => 'Date',
        ],
    ];

    /**
     * Only reading is possible
     *
     * @return array An associative array.
     */
    public function getPermissionOptions()
    {
        return [
            'read' => 'View changes of all content',
        ];
    }

    /**
     * Customize the edit view to return the changed attributes as JSON. Using
     * this method / action so that a new routing rule doesn't need to be created
     *
     * @param  int                      $id Model key
     * @return Illuminate\Http\Response
     */
    public function edit($id)
    {
        $change = Change::findOrFail($id);

        return Response::json([
            'action' => $change->action,
            'title' => $change->title,
            'admin' => $change->admin->getAdminTitleHtmlAttribute(),
            'admin_edit' => $change->admin->getAdminEditAttribute(),
            'date' => $change->getHumanDateAttribute(),
            'attributes' => $change->attributesForModal(),
        ]);
    }
}
