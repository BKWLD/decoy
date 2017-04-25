<?php
// Sidebar pagination can be found in standard.php

// Standard full list pagination
if ((empty($layout) || $layout == 'full') && method_exists($listing, 'links')) {
	echo view('decoy::shared.pagination.paginator', [
		'paginator' => $listing->appends([
			'query' => request('query'),
			'sort' => request('sort'),
			'count' => request('count'),
		]),
	])->render();
}
