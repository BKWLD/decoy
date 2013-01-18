<?// Render pagination ?>

<? if ($sidebar): ?>
	<? if ($count > count($iterator)): ?>
		<a href="<?=$child_route?>" class="btn btn-small btn-block full-list">See full list of related <?=strtolower($title)?></a>
	<? endif ?>
<? elseif (method_exists($listing, 'links')): ?>
	<?=$listing->appends(array(
		'query' => Input::get('query'),
		'sort' => Input::get('sort'),
		))->links(); ?>
<? endif?>