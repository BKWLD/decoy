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
		<?= HTML::webpackAssetTag('admin.css') ?>
	</head>
	<body class="<?=Decoy::bodyClass()?>">
		
		<?// Sidebar ?>
		<?= View::make('decoy::layouts.sidebar._sidebar')->render() ?>

		<?// Header ?>
		<?= View::make('decoy::layouts._header', $__data)->render() ?>
		<?= empty($breadcrumbs) ? View::make('decoy::layouts._breadcrumbs', $__data)->render() : $breadcrumbs; ?>
		<?= View::make('decoy::layouts._notifications', $__data)->render()?>

		<?// The main page content ?>
		<div id="main">
			<?= $content?>
		</div>
	
	<?// Footer embeds ?>
	<?= HTML::webpackAssetTag('admin.js') ?>
	<?= View::make('decoy::layouts._wysiwyg')->render() ?>
</body>
</html>