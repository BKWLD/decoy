<?// Render pagination ?>

<? if ($sidebar): ?>
	<? if ($count > count($iterator)): ?>
		<a href="<?=route($controller.'@child', $parent_id)?>" class="btn btn-small btn-block full-list">See full list of related <?=strtolower($title)?></a>
	<? endif ?>
<? elseif (method_exists($listing, 'links')): ?>
	<?=$listing->links(); ?>
<? endif?>