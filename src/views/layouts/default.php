<!DOCTYPE html>
<!--[if lt IE 7]>      <html class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>         <html class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>         <html class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js"> <!--<![endif]-->
	<head>
		<meta charset="utf-8"/>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
		<?= HTML::title() ?>
		<meta name="viewport" content="width=device-width"/>
		<meta name="csrf" content="<?=Session::getToken()?>"/>
		<?= View::make('decoy_published::layout.buk_builder._header') ?>
		<script src="/packages/bkwld/decoy/ckeditor/ckeditor.js"></script>
	</head>
	<body class="<?=HTML::bodyClass()?>">
		
		<?// Nav ?>
		<?= View::make('decoy::layouts._nav') ?>
		
		<?// If breadcrumbs haven't been nested, manually render now  ?>
		<?= empty($breadcrumbs) ? View::make('decoy::layouts._breadcrumbs') : $breadcrumbs ?>
	
		<?// Container for notifications ?>
		<div class='notifications top-right'></div>
		
		<?// The main page content ?>
		<div id="main" class="container">
			<?= $content?>
		</div>
		
	<?= View::make('decoy_published::layout.buk_builder._footer') ?>
</body>
</html>