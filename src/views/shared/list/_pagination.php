<?// Render pagination ?>

<? if ($sidebar): ?>
	<? if ($count > count($listing)): ?>
		<a href="<?=HTML::relative('index', $parent_id, $controller)?>" class="btn btn-small btn-block full-list">See full list of related <?=strtolower($title)?></a>
	<? endif ?>
<? elseif (method_exists($listing, 'links')): ?>
	<?=$listing->appends(array(
		'query' => Input::get('query'),
		'sort' => Input::get('sort'),
		))->links(); ?>
<? endif?>