<?php

// This controller deals with blocks of contents that can be used anywhere.
// It's like a categorized key/value store
class Decoy_Content_Controller extends Decoy_Base_Controller {

	// Display all the content fields
	public function get_index() {
		
		// Get organized content results
		$categories = Content::organized();
				
		// Render the view
		$this->layout->nest('content', 'decoy::content.index', array(
			'categories' => $categories
		));
		
	}
	
	// Handle the submission of the content form
	public function post_index() {
		
		// Loop through inputs and update the value if the slug exists
		foreach(Input::all() as $slug => $value) {
			
			// If the slug is for a delete checkbox or replace file field, do nothing.
			// All of this behavior is triggered on the old- hidden field
			if (Str::is(UPLOAD_DELETE.'*', $slug) || Str::is(UPLOAD_REPLACE.'*', $slug)) continue;
			
			// If the slug is old-* (making this a file field), compute the expected slug
			if (Str::is(UPLOAD_OLD.'*', $slug)) $slug = substr($slug, 4);
			
			// Move replace-* named files to their expected location
			self::move_replace_file_input($slug);
			
			// Update the content pair
			Content::update($slug, $value);
		}

		// Redirect back to the get view
		return Redirect::to(URL::current());
		
	}
	
}