<?// Just the slug field for the display module?>

<? 
$url_link = 'URI';
if (!empty($item->slug)) {
	$prepend = '/';
	if ($url = $item->getUriAttribute()) {
		$url_link = '<a href="'.$url.'">URI</a>';
		$prepend = preg_replace('#/[\w-]+$#', '/', parse_url(rtrim($url,'/'), PHP_URL_PATH));
	}
	echo Former::text('slug')->blockHelp('Used to form the '.$url_link.' for this content.')->prepend($prepend);
}
?>