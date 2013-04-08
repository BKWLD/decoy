<?// Adds alt tags to images?>

<legend>SEO</legend>

<?
	$span = empty($related) ? 'span9' : 'span6';
	echo Former::text('alt', '"Alt" attribute')->class($span)->blockHelp('Content for the image tag "alt" attribute');
?>