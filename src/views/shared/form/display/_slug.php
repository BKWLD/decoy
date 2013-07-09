<?// Just the slug field for the display module?>

<? 
$url_link = 'deep link';
if (!empty($item->slug)) {
	$prepend = '/';
	$span = empty($related) ? 'span7' : 'span4'; // If related is set, this is on a 2 columned form
	if ($url = $item->deepLink()) {
		$url_link = '<a href="'.$url.'">deep link</a>';
		$prepend = preg_replace('#/[\w-]+$#', '/', parse_url($url, PHP_URL_PATH));
	}
	echo Former::text('slug')->blockHelp('Used to form the '.$url_link.' to this content')->prepend($prepend)->class($span);
}
?>