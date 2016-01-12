<?

$back = app('decoy.breadcrumbs')->back();
$breadcrumbs = app('decoy.breadcrumbs')->get();
$breadcrumb_count = count($breadcrumbs);
?>

<div class="breadcrumbs affixable" data-top="0">
	<?// This is here only to apply a max width to the contents ?>
	<div class="inner">

		<?// Back button is first so floating works correctly ?>
		<? if ($back && !Route::is('decoy::account@forgot', 'decoy::account@reset')): ?>
			<a href="<?=$back?>" class="back">
				<span class="glyphicon glyphicon-arrow-left"></span>
				Back to listing
			</a>
		<? endif?>

		<?// The breadcrumbs ?>
		<a href="/admin"><span class="glyphicon glyphicon-home"></span></a>
		<? foreach($breadcrumbs as $url => $name): ?>
			<a href="<?=$url?>"><?=$name?></a>
			<? if ($breadcrumb_count-- !== 1): ?>
				<span class="glyphicon glyphicon-chevron-right"></span>
			<? endif ?>
		<? endforeach ?>

	</div>
</div>
