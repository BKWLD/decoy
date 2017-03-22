<?php 
// Sidebar pagination can be found in standard.php

// Standard full list pagination
if ((empty($layout) || $layout == 'full') && method_exists($listing, 'links')) {
	echo view('decoy::shared.list._paginator', [
		'paginator' => $listing->appends([
			'query' => Request::get('query'),
			'sort' => Request::get('sort'),
			'count' => Request::get('count'),
		]),
	])->render();
}
