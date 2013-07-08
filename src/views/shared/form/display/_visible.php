<?// Just the visbibility field for the display module?>

<?= Former::radios('visible', 'Availability')->inline()->radios(array(
	'Visible' => array('value' => '1', 'checked' => empty($item) ? true : $item->visible == 1),
	'Hidden' => array('value' => '', 'checked' => empty($item) ? false : $item->visible != 1),
))->blockHelp((!empty($item) && $url = $item->deepLink()) ? 
	'If hidden, this content will be accessible via <a href="'.$url.'">deep link</a> but not in lists' : 
	'If hidden, this content will be completely inaccessible') ?>