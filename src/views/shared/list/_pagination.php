<?// Related sidebar pagination ?>
<? if (!empty($layout) && $layout != 'full'): ?>
	<? if ($count > count($listing)): ?>
		<a href="<?=DecoyURL::relative('index', $parent_id, $controller)?>" class="btn btn-small btn-block full-list">See full list of related <?=strtolower($title)?></a>
	<? endif ?>

<?// Standard full list pagination ?>
<? elseif (method_exists($listing, 'links')): ?>
	<?=$listing->appends(array(
		'query' => Input::get('query'),
		'sort' => Input::get('sort'),
		'count' => Input::get('count'),
		))->links(); ?>
<? endif?>