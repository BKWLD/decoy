<?// Sidebar pagination can be found in standard.php?>

<?// Standard full list pagination ?>
<? if ((empty($layout) || $layout == 'full') && method_exists($listing, 'links')): ?>
	<?=$listing->appends(array(
		'query' => Input::get('query'),
		'sort' => Input::get('sort'),
		'count' => Input::get('count'),
		))->links(); ?>
<? endif?>