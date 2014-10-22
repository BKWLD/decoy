<?// Populated in part by controllers and a composer?>

<?
// If no breacrumbs, show nothing
if (empty($breadcrumbs)) return;
?>

<div class="breadcrumbs affixable" data-top="0">
	<?// This is here only to apply a max width to the contents ?>
	<div class="inner">
		<?// The breadcrumbs ?>
		<a href="/admin"><span class="glyphicon glyphicon-home"></span></a>
		<? foreach($breadcrumbs as $url => $name): ?>
			<a href="<?=$url?>"><?=$name?></a>
			<? if ($breadcrumb_count-- !== 1): ?>
				<span class="glyphicon glyphicon-chevron-right"></span>
			<? endif ?>
		<? endforeach ?>
		
		<?// Back button ?>
		<? if (!empty($back) && !Str::is('decoy\account*', Route::currentRouteName())): ?>
			<a href="<?=$back?>" class="back">
				<span class="glyphicon glyphicon-arrow-left"></span>
				Back to listing
			</a>
		<? endif?>
	</div>
</div>