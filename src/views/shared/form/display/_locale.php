<?php

// Only show if more than one locale
if (($locales = Config::get('decoy::site.locales')) && is_array($locales) && count($locales) <= 1) return;

// Create radios config
$config = Bkwld\Library\Laravel\Former::radioArray($locales);

// Look for other localizations of this record
if ($item && ($localizations = $item->other_localizations)) {
	$original = $config;
	$config = [];

	// Group other ones by their locale slug and loop though
	$localizations = $localizations->groupBy('locale');
	foreach($original as $label => $options) {

		// If a locale is already in use, disable it
		if ($sibling = $localizations->get($options['value'])) {
			$sibling = $sibling[0]; // The groupBy makes an array for its value
			$options['disabled'] = true;
			$label = "<span class='locale-label'>{$label} - In use by <a href='".DecoyURL::relative('edit', $sibling->getKey())."'>".$sibling->title().'</a></span>';
			$config[$label] = $options;

		// Else, don't touch
		} else $config[$label] = $options;
	}
}

echo Former::radios('locale')
	->radios($config)
	->addGroupClass('locale')
	->blockHelp('This content will only be shown to viewers of the selected locale.  You cannot assign it to a locale that has already been used to localize this content.');