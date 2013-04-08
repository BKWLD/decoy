<?// Populated in part by controllers and a composer?>

<?
// If no breacrumbs, show nothing
if (empty($breadcrumbs)) return;
?>

<div class="breadcrumbs">
	<div class="container">
		
		<?// The breadcrumbs ?>
		<a href="/admin"><i class="icon-home"></i></a>
		<? foreach($breadcrumbs as $url => $name): ?>
			<a href="<?=$url?>"><?=$name?></a>
			<? if ($breadcrumb_count-- !== 1): ?>
				<i class="icon-chevron-right"></i>
			<? endif ?>
		<? endforeach ?>
		
		<?// Back button ?>
		<? if (!empty($back)): ?>
			<a href="<?=$back?>" class="back">
				<i class="icon-arrow-left"></i>
				Back to listing
			</a>
		<? endif?>
	</div>
</div>