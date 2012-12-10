<?=render('decoy::shared.form._header', array(
	'title'       => 'General Content',
	'controller'  => 'decoy::content',
	'description' => 'This page contains simple fields for content that appears throughout the site.',
	'no_legend'   => true,
))?>

	<?// Loop through categories of content pairs ?>
	<? foreach($categories as $category => $pairs) { ?>
		<legend><?=$category?></legend>
		
		<?
		// Loop throug the pairs
		foreach($pairs as $pair) {
			switch($pair->type) {
				case 'text': 
					echo Former::text($pair->slug, $pair->label)->class('span9')->value($pair->value); 
					break;
				case 'textarea': 
					echo Former::textarea($pair->slug, $pair->label)->class('span9')->value($pair->value); 
					break;
				case 'wysiwyg':
					echo Former::textarea($pair->slug, $pair->label)->class('span9 wysiwyg')->value($pair->value); 
					break;
				case 'image':
					echo HTML::image_upload($pair->value, $pair->slug, $pair->label);
			}
		}
	}
	?>

<?=render('decoy::shared.form._footer', array(
	'controller'  => 'decoy::content',
))?>

