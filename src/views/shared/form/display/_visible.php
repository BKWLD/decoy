<?// Just the visbibility field for the display module?>

<?= Former::radios('visible', 'Availability')->inline()->radios(array(
	'Visible' => array('value' => 1, 'checked' => true),
	'Hidden' => array('value' => 0),
))->blockHelp(($url = $item->deepLink()) ? 
	'If hidden, this content will be accessible via <a href="'.$url.'">deep link</a> but not in lists' : 
	'If hidden, this content will be completely inaccessible') ?>