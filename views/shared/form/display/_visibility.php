<?php 
// Make help
$help = (!empty($item) && $uri = $item->getUriAttribute()) ?
	'If "Private", this content will be accessible to logged in Admins via it\'s <a href="'.$uri.'">URI</a> but will not show in lists.' :
	'If "Private", this content will be completely inaccessible.';

// Check if they have permission
if (!app('decoy.user')->can('publish', $controller)) {
	$status =  $item && $item->public ? 'Published' : 'Draft';
	echo Former::note('Status', $status)->blockHelp($help);
	return;
}

// Redner radios
echo Former::radios('public', 'Visibility')->inline()->radios(array(
	'Public' => array('value' => '1', 'checked' => empty($item) ? true : $item->public),
	'Private' => array('value' => '0', 'checked' => empty($item) ? false : !$item->public),
))->blockHelp($help);
