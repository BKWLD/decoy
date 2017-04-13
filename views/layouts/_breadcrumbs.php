<?php

$back = app('decoy.breadcrumbs')->back();
$breadcrumbs = app('decoy.breadcrumbs')->get();
$breadcrumb_count = count($breadcrumbs);
?>

<div class="breadcrumbs affixable" data-top="0">
	<?php // This is here only to apply a max width to the contents ?>
	<div class="inner">

		<?php // Back button is first so floating works correctly ?>
		<?php if ($back && !Route::is('decoy::account@forgot', 'decoy::account@reset')): ?>
			<a href="<?=$back?>" class="back">
				<span class="glyphicon glyphicon-arrow-left"></span>
				<?php echo __('decoy::breadcrumbs.back_to_listing'); ?>
			</a>
		<?php endif?>

		<?php // The breadcrumbs ?>
		<a href="/admin"><span class="glyphicon glyphicon-home"></span></a>
		<?php foreach($breadcrumbs as $url => $name): ?>
			<a href="<?=$url?>"><?=$name?></a>
			<?php if ($breadcrumb_count-- !== 1): ?>
				<span class="glyphicon glyphicon-chevron-right"></span>
			<?php endif ?>
		<?php endforeach ?>

	</div>
</div>
