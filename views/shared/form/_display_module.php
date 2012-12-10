<?// Adds a slug input field and visibility toggle?>

<legend>
		Display
		<? if (!empty($item) && !empty($url)): ?>
			<a href="<?=$url?>" class="btn btn-small pull-right"><i class="icon-bookmark"></i> View</a>
		<? endif ?>
</legend>

<? 
$url_link = 'deep link';
if (!empty($item->slug)) {
	$prepend = '/';
	$span = empty($related) ? 'span7' : 'span4'; // If related is set, this is on a 2 columned form
	if (!empty($url)) {
		$url_link = '<a href="'.$url.'">deep link</a>';
		$prepend = preg_replace('#/[\w-]+$#', '/', parse_url($url, PHP_URL_PATH));
	}
	echo Former::text('slug')->blockHelp('Used to form the '.$url_link.' to this content')->prepend($prepend)->class($span);
}
?>

<?= Former::radios('visible', 'Availability')->inline()->radios(array(
	'Visible' => array('value' => 1, 'checked' => true),
	'Hidden' => array('value' => 0),
))->blockHelp('If hidden, this content will be accessible via '.$url_link.' but not in lists') ?>