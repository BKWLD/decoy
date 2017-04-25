<?php
// Make help
$help = (!empty($item) && $uri = $item->getUriAttribute()) ?
    __('decoy::display.visibility.help', ['uri' => $uri]) :
    __('decoy::display.visibility.alternate_help');

// Check if they have permission
if (!app('decoy.user')->can('publish', $controller)) {
    $status = $item && $item->public ? __('decoy::display.visibility.published') : __('decoy::display.visibility.draft');
    echo Former::note('Status', $status)->blockHelp($help);
    return;
}

// Render radios
echo Former::radios('public', __('decoy::display.visibility.label'))->inline()->radios(array(
    __('decoy::display.visibility.public') => array('value' => '1', 'checked' => empty($item) ? true : $item->public),
    __('decoy::display.visibility.private') => array('value' => '0', 'checked' => empty($item) ? false : !$item->public),
))->blockHelp($help);
