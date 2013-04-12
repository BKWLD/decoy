<?php namespace Bkwld\Decoy\Routing;

// Dependencies
use Bkwld\Decoy\Controllers\Base;

/**
 * This class tries to figure out if the injected controller has parents
 * and who they are.
 */
class Ancestry {
	
	// DI
	$this->controller;
	
	/**
	 * Inject dependencies
	 * @param Bkwld\Decoy\Controllers\Base $controller
	 */
	public function __construct(Base $controller) {
		$this->controller = $controller;
	}
	
	/**
	 * Test if the current route is serviced by has many and/or belongs to.  These
	 * are only true if this controller is acting in a child role
	 * 
	 */
	public function isChildRoute() {
		if (empty($this->CONTROLLER)) throw new Exception('$this->CONTROLLER not set');
		return $this->actionIsChild()
			|| $this->parentIsInInput()
			|| $this->isActingAsRelated();
	}
	
	// Return a boolean for whether the parent relationship represents a many to many
	public function isChildInManyToMany() {
		if (empty($this->SELF_TO_PARENT)) return false;
		$model = new $this->MODEL; // Using the 'Model' class alias didn't work, was the parent
		if (!method_exists($model, $this->SELF_TO_PARENT)) return false;
		$relationship = $model->{$this->SELF_TO_PARENT}();
		return is_a($relationship, 'Laravel\Database\Eloquent\Relationships\Has_Many_And_Belongs_To');
	}
	
	// Test if the current route is one of the full page has many listings or a new
	// page as a child
	public function actionIsChild() {
		return Request::route()->is($this->CONTROLLER.'@child')
			|| Request::route()->is($this->CONTROLLER.'@new_child')
			|| Request::route()->is($this->CONTROLLER.'@edit_child');
	}
	
	// Test if the current route is one of the many to many XHR requests
	public function parentIsInInput() {
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
	public function isActingAsRelated() {
		$handles = Bundle::option('decoy', 'handles');
		$controller_name = substr($this->CONTROLLER, strlen($handles.'.'));
		return strpos(Request::route()->action['uses'], $this->CONTROLLER.'@') === false
			&& strpos(URI::current(), '/'.$controller_name.'/') === false
			&& strpos(Request::route()->action['uses'], '@edit') !== false;
	}
	
	// Guess at what the parent controller is by examing the route or input varibles
	public function deduceParentController() {
		
		// If a child index view, get the controller from the route
		if ($this->actionIsChild()) {
			return Request::segment(1).'.'.Request::segment(2);
		
		// If one of the many to many xhr requests, get the parent from Input
		} elseif ($this->parentIsInInput()) {
			$input = BKWLD\Laravel\Input::json_and_input();
			return $input['parent_controller'];
		
		// If this controller is a related view of another, the parent is the main request	
		} else if ($this->isActingAsRelated()) {
			return Request::route()->controller;
		}
	}
	
	// Guess as what the relationship function on the parent model will be
	// that points back to the model for this controller by using THIS
	// controller's name.
	// returns - The string name of the realtonship
	public function deduceParentRelationship() {
		$handles = Bundle::option('decoy', 'handles');
		$relationship = substr($this->CONTROLLER, strlen($handles.'.'));
		if (!method_exists($this->PARENT_MODEL, $relationship)) {
			throw new Exception('Parent relationship missing, looking for: '.$relationship);
		}
		return $relationship;
	}
	
	// Guess at what the child relationship name is.  This is typically the same
	// as the parent model.  For instance, Post has many Image.  Image will have
	// a function named "post" for it's relationship
	public function deduceChildRelationship() {
		$relationship = strtolower($this->PARENT_MODEL);
		if (!method_exists($this->MODEL, $relationship)) {
			
			// Try controller name instead, in other words the plural version.  It might be
			// named this if it's a many-to-many relationship
			$handles = Bundle::option('decoy', 'handles');
			$relationship = strtolower(substr($this->PARENT_CONTROLLER, strlen($handles.'.')));
			if (!method_exists($this->MODEL, $relationship)) {
				throw new Exception('Child relationship missing on '.$this->MODEL);
			}
		}
		return $relationship;
	}
	
}