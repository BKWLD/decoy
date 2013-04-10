<?php

// Auto-publish the assets when developing locally
if (Request::is_env('local') && !Request::cli()) {
	
	// Had to use this route rather than Command::run(), I couldn't
	// trigger deploy for some reason.
	ob_start();
	$publisher = new Laravel\CLI\Tasks\Bundle\Publisher;
	$publisher->publish('decoy');
	$publisher->publish('croppa');
	ob_end_clean(); // Supress the output from the above, which does an echo
}

// Change former's required field HTML
Former\Config::set('required_text', ' <i class="icon-exclamation-sign js-tooltip required" title="Required field"></i>');

// Tell former to include unchecked checkboxes in the post
Former\Config::set('push_checkboxes', true);
