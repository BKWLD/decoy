<?php

/**
 * Fake images
 */
class Fake_Image extends Fake {
	
	// Get a photo of a person
	public function person($dst = null) {
		return $this->category($dst, 'people');
	}
	
	// Get an avatar image.  If dst is set, download the file to that directory and return
	// it's absolute path.  Otherwise, just return the remote URL.
	public function random($dst = null) {
		return $this->category($dst);
	}
	
	// Get a picture from a specific category.  Category options are listed
	// here: http://lorempixel.com/
	public function category($dst, $category = null) {
		
		// Form the URL
		$url = 'http://lorempixel.com/1600/1600/';
		if (!$dst) return $url;
		
		// Add the category
		if ($category) $url .= $category.'/';
		
		// Form the new filename
		if (substr($dst, -1, 1) != '/') $dst .= '/';
		$dst = $dst.md5(uniqid()).'.jpg';
		
		// Save the file to the dst directory
		if (is_writable(dirname($dst)) && copy($url, $dst)) return $dst;
		return false;
		
	}
	
}