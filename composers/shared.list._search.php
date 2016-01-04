<?php

// Massage data for the search intera
View::composer('decoy::shared.list._search', function($view) {

	// Massage the shorthand search config options
	if (isset($view->search)) {
		$search = new Bkwld\Decoy\Input\Search();
		$view->search = $search->longhand($view->search);
	}

});