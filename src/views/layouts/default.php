<!DOCTYPE html>
<!--[if lt IE 7]>  <html class="no-js lt-ie10 lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>     <html class="no-js lt-ie10 lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>     <html class="no-js lt-ie10 lt-ie9"> <![endif]-->
<!--[if IE 9]>     <html class="no-js lt-ie10"> <![endif]-->
<!--[if gt IE 9]><!--> <html class="no-js"> <!--<![endif]-->
	<head>
		<meta charset="utf-8"/>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
		<?= Decoy::title() ?>
		<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no"/>
		<meta name="csrf" content="<?=Session::getToken()?>"/>
		<link rel="stylesheet" href="<?=HTML::grunt('/css/admin/vendor.css')?>"/>
		<link rel="stylesheet" href="<?=HTML::grunt('/css/admin/style.css')?>"/>
	</head>
	<body class="<?=Decoy::bodyClass()?>">
		
		<?// Sidebar ?>
		<?= View::make('decoy::layouts.sidebar._sidebar') ?>

		<?// Header ?>
		<?= View::make('decoy::layouts._header', $__data) ?>
		<?= empty($breadcrumbs) ? View::make('decoy::layouts._breadcrumbs', $__data) : $breadcrumbs; ?>
		<?= View::make('decoy::layouts._notifications', $__data)?>

		<?// The main page content ?>
		<div id="main">
			<?= $content?>
		</div>
	
	<?// Footer embeds ?>
	<? if (App:: isLocal()): ?><script> var require = { urlArgs: "bust=" + (new Date()).getTime() }; </script><? endif ?>
	<script src="<?=HTML::grunt('/js/vendor/require-jquery.js')?>"></script>
	<?= View::make('decoy::layouts._wysiwyg') ?>
	<script src="<?=HTML::grunt('/js/admin/main.js')?>"></script>
</body>
</html>