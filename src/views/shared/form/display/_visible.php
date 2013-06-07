<?// Just the visbibility field for the display module?>

<?= Former::radios('visible', 'Availability')->inline()->radios(array(
	'Visible' => array('value' => 1, 'checked' => true),
	'Hidden' => array('value' => 0),
))->blockHelp('If hidden, this content will be accessible via '.$url_link.' but not in lists') ?>