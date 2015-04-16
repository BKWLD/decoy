<?php namespace Bkwld\Decoy\Controllers;

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
	
}