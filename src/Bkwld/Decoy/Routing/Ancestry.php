<?php namespace Bkwld\Decoy\Routing;

/**
 * This class tries to figure out if the injected controller has parents
 * and who they are.
 */
class Ancestry {
	
	/**
	 * Inject dependencies
	 */
	public function __construct() {
		
	}
	
	/**
	 * Test if the current route is serviced by has many and/or belongs to.  These
	 * are only true if this controller is acting in a child role
	 * 
	 */
	public function is_child_route() {
		if (empty($this->CONTROLLER)) throw new Exception('$this->CONTROLLER not set');
		return $this->action_is_child()
			|| $this->parent_in_input()
			|| $this->acting_as_related();
	}
	
	// Test if the current route is one of the full page has many listings or a new
	// page as a child
	private function action_is_child() {
		return Request::route()->is($this->CONTROLLER.'@child')
			|| Request::route()->is($this->CONTROLLER.'@new_child')
			|| Request::route()->is($this->CONTROLLER.'@edit_child');
	}
	
	// Test if the current route is one of the many to many XHR requests
	private function parent_in_input() {
		// This is check is only allowed if the request is for this controller.  If other
		// controller instances are instantiated (like via Controller::resolve()), they 
		// were not designed to be informed by the input.  Using action[uses] rather than like
		// ->controller because I found that controller isn't always set when I need it.  Maybe
		// because this is all being invoked from the constructor.
		if (strpos(Request::route()->action['uses'], $this->CONTROLLER.'@') === false) return false;		
		return isset(Input::get('parent_controller');
	}
	
	// Test if the controller must be used in rendering a related list within another.  In other
	// words, the controller is different than the request and you're on an edit page.  Had to
	// use action[uses] because Request::route()->controller is sometimes empty.  
	// Request::route()->action['uses'] is like "admin.issues@edit".  We're also testing that
	// the controller isn't in the URI.  This would never be the case when something was in the
	// sidebar.  But without it, deducing the breadcrumbs gets confused because controllers get
	// instantiated not on their route but aren't the children of the current route.
	private function acting_as_related() {
		$handles = Bundle::option('decoy', 'handles');
		$controller_name = substr($this->CONTROLLER, strlen($handles.'.'));
		return strpos(Request::route()->action['uses'], $this->CONTROLLER.'@') === false
			&& strpos(URI::current(), '/'.$controller_name.'/') === false
			&& strpos(Request::route()->action['uses'], '@edit') !== false;
	}
	
}