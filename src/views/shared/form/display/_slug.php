<?// Just the slug field for the display module?>

<? 
$url_link = 'deep link';
if (!empty($item->slug)) {
	$prepend = '/';
	if ($url = $item->deepLink()) {
		$url_link = '<a href="'.$url.'">deep link</a>';
		$prepend = preg_replace('#/[\w-]+$#', '/', parse_url(rtrim($url,'/'), PHP_URL_PATH));
	}
	echo Former::text('slug')->blockHelp('Used to form the '.$url_link.' to this content')->prepend($prepend);
}
?>