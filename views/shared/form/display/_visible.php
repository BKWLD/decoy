<?
// Make help 
$help = (!empty($item) && $uri = $item->getUriAttribute()) ? 
	'If "Draft", this content will be accessible via it\'s <a href="'.$uri.'">URI</a> but not in lists.' : 
	'If "Draft", this content will be completely inaccessible.';

// Check if they have permission
if (!app('decoy.user')->can('publish', $controller)) {
	$status =  $item && $item->visible ? 'Published' : 'Draft';
	echo Former::note('Status', $status)->blockHelp($help);
	return;
}

// Redner radios
echo Former::radios('visible', 'Status')->inline()->radios(array(
	'Published' => array('value' => '1', 'checked' => empty($item) ? true : $item->visible == 1),
	'Draft' => array('value' => false, 'checked' => empty($item) ? false : $item->visible != 1),
))->blockHelp($help);