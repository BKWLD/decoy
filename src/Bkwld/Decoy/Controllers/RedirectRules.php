<?php namespace Bkwld\Decoy\Controllers;

/**
 * Allow admin to manage redirection rules
 */
class RedirectRules extends Base {
	protected $title = 'Redirects';
	protected $description = 'Rules that redirect an internal URL path to another.';
	protected $columns = array(
		'Rule' => 'title',
	);
	protected $search = array(
		'from',
		'to',
		'code' => array(
			'type' => 'select',
			'options' => 'Bkwld\Decoy\Models\RedirectRule::$codes',
		),
		'label',
	);
}