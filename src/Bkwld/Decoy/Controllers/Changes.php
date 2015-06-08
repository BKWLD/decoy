<?php namespace Bkwld\Decoy\Controllers;

/**
 * A log of model changes, used for auditing Admin activity. Can also be used
 * as a source for recovering changed / deleted content.
 */
class Changes extends Base {

	protected $description = 'A log of actions that can be used to audit <b>Admin</b> activity or recover content.';
	protected $columns = [
		'Activity' => 'getAdminTitleHtmlAttribute',
		// 'Credit' => 'getCreditAttribute',
	];
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

}