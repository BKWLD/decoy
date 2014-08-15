<?// Populated in part by controllers and a composer?>

<?
// If no breacrumbs, show nothing
if (empty($breadcrumbs)) return;
?>

<div class="breadcrumbs">
	<div class="container">
		
		<?// The breadcrumbs ?>
		<a href="/admin"><i class="glyphicon glyphicon-home"></i></a>
		<? foreach($breadcrumbs as $url => $name): ?>
			<a href="<?=$url?>"><?=$name?></a>
			<? if ($breadcrumb_count-- !== 1): ?>
				<i class="glyphicon glyphicon-chevron-right"></i>
			<? endif ?>
		<? endforeach ?>
		
		<?// Back button ?>
		<? if (!empty($back) && !Str::is('decoy\account*', Route::currentRouteName())): ?>
			<a href="<?=$back?>" class="back">
				<i class="glyphicon glyphicon-arrow-left"></i>
				Back to listing
			</a>
		<? endif?>
	</div>
</div>