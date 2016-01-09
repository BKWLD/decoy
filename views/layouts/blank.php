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
		<meta name="csrf-token" content="<?=csrf_token()?>"/>

		<? if (file_exists(public_path('dist/admin.css'))): ?>
			<link href='/dist/admin.css' rel='stylesheet' charset='utf-8'>
		<? endif ?>

	</head>
	<body class="<?=Decoy::bodyClass()?>">

		<?// The main page content ?>
		<? if (isset($content)): ?>
			<div id="main">
				<?= $content?>
			</div>
		<? endif ?>

		<? if (file_exists(public_path('dist/admin.js'))): ?>
			<script src='/dist/admin.js' charset='utf-8'></script>
		<? else: ?>
			<script src='http://localhost:8080/dist/admin.js' charset='utf-8'></script>
		<? endif ?>

</body>
</html>
