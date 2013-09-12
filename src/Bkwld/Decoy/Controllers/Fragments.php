<?php namespace Bkwld\Decoy\Controllers;

// Dependencies
use Former;

class Fragments extends Base {
	
	// Main fragments view
	public function index() {
		Former::populate(Model::values());
		$this->layout->nest('content', 'decoy::fragments.index', array(
			'fragments' => Model::organized(),
		));
	}
	
}