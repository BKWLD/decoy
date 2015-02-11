<?// Just the visbibility field for the display module?>

<?= Former::radios('visible', 'Availability')->inline()->radios(array(
	'Visible' => array('value' => '1', 'checked' => empty($item) ? true : $item->visible == 1),
	'Hidden' => array('value' => '', 'checked' => empty($item) ? false : $item->visible != 1),
))->blockHelp((!empty($item) && $uri = $item->getUriAttribute()) ? 
	'If hidden, this content will be accessible via it\'s <a href="'.$uri.'">URI</a> but not in lists.' : 
	'If hidden, this content will be completely inaccessible.') ?>