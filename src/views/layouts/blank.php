<!DOCTYPE html>
<!--[if lt IE 7]>      <html class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>         <html class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>         <html class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js"> <!--<![endif]-->
	<head>
		<meta charset="utf-8"/>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
		<?= Decoy::title() ?>
		<meta name="viewport" content="width=device-width"/>
		<meta name="csrf" content="<?=Session::getToken()?>"/>
		<link rel="stylesheet" href="<?=HTML::grunt('/css/admin/vendor.css')?>"/>
		<link rel="stylesheet" href="<?=HTML::grunt('/css/admin/style.css')?>"/>
		<script src="/packages/bkwld/decoy/ckeditor/ckeditor.js"></script>
		<script src="/packages/bkwld/decoy/ckfinder/ckfinder.js"></script>
	</head>
	<body class="<?=Decoy::bodyClass()?>">
		
		<?// The main page content ?>
		<? if (isset($content)): ?>
			<div id="main">
				<?= $content?>
			</div>
		<? endif ?>
		
	<script src="<?=HTML::grunt('/js/vendor/require-jquery.js')?>"></script>
	<script src="<?=HTML::grunt('/js/admin/main.js')?>"></script>
</body>
</html>