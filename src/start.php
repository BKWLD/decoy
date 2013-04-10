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
