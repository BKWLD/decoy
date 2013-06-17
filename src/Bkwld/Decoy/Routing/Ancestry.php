<?php namespace Bkwld\Decoy\Routing;

// Dependencies
use Bkwld\Decoy\Controllers\Base;
use Bkwld\Decoy\Exception;
use Bkwld\Decoy\Routing\Wildcard;
use Bkwld\Decoy\Routing\UrlGenerator;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Request;

/**
 * This class tries to figure out if the injected controller has parents
 * and who they are.  There is an assumption with this logic that ancestry
 * only matters to controllers that were resolved through Wildcard
 */
class Ancestry {
	
	/**
	 * Inject dependencies
	 * @param Bkwld\Decoy\Controllers\Base $controller
	 * @param Bkwld\Decoy\Routing\Wildcard $wildcard
	 * @param Symfony\Component\HttpFoundation\Request $input
	 */
	private $controller;
	private $router;
	private $wildcard;
	public function __construct(Base $controller, Wildcard $wildcard, Request $input) {
		$this->controller = $controller;
		$this->wildcard = $wildcard;
		$this->input = $input;
	}
	
	/**
	 * Test if the current route is serviced by has many and/or belongs to.  These
	 * are only true if this controller is acting in a child role
	 * 
	 */
	public function isChildRoute() {
		return $this->requestIsChild()
			|| $this->parentIsInInput()
			|| $this->isActingAsRelated();
	}
	
	/**
	 * Test if the current URL is for a controller acting in a child capacity.  We're only
	 * checking wilcarded routes (not any that were explictly registered), because I think
	 * it unlikely that we'd ever explicitly register routes to be children of another.
	 */
	public function requestIsChild() {
		
		// Only perform check if the route is for a child
		return $this->wildcard->detectIfChild()
		
			// ... and make sure the passed controller is the child that was detected
			&& $this->isRouteController();
	}

	/**
	 * Test if the current route is one of the many to many XHR requests
	 */
	public function parentIsInInput() {

		// This is check is only allowed if the request is for this controller.  If other
		// controller instances are instantiated, they were not designed to be informed by the input.
		if (!$this->isRouteController()) return false;
		
		// Check for a property in the AJAX input of 'parent_controller'
		return $this->input->has('parent_controller');
	}
	
	/**
	 * Test if the controller may be being used in rendering a related list within another.  In other
	 * words, the controller is different than the request and you're on an edit page.
	 */
	public function isActingAsRelated() {
		
		// We're also testing that this controller isn't in the URI.  This would never be the case when 
		// something was in the sidebar.  But without it, deducing the breadcrumbs gets confused because 
		// controllers get instantiated not on their route but aren't the children of the current route.
		// So I convert the controller to it's URL representation and then make sure it is not present
		// in the current URL.
		$generator = new UrlGenerator($this->input->path());
		$test = $generator->controller($this->controller->controller()); // ex: /admin/articles
		if (strpos('/'.$this->input->path(), $test) !== false) return false;
		
		// Check that we're on an edit page
		return $this->wildcard->detectAction() === 'edit';

	}
	
	/**
	 * Test if the request is for a controller
	 */
	public function isRouteController() {
		return $this->wildcard->detectController() === get_class($this->controller);
	}
	
	/**
	 * Return a boolean for whether the parent relationship represents a many to many.  This is
	 * different from isChildRoute() because it also checks what kind of relationship the child
	 * is in.
	 */
	public function isChildInManyToMany() {
		$relationship = $this->controller->selfToParent();
		if (!$relationship) return false;
		$model = $this->controller->model();
		if (!method_exists($model, $relationship)) return false;
		$model = new $model; // Needed to be a simple string to work
		return is_a($model->{$relationship}(), 'Illuminate\Database\Eloquent\Relations\BelongsToMany');
	}
	
	/**
	 * Guess at what the parent controller is by examing the route or input varibles
	 * @return string ex: Admin\NewsController
	 */
	public function deduceParentController() {
		
		// If a child index view, get the controller from the route
		if ($this->requestIsChild()) {
			return $this->wildcard->getParentController();
		
		// If one of the many to many xhr requests, get the parent from Input
		} elseif ($this->parentIsInInput()) {
			return $this->input->get('parent_controller');
		
		// If this controller is a related view of another, the parent is the main request	
		} else if ($this->isActingAsRelated()) {
			return $this->wildcard->detectController();
		
		// No parent found
		} else return false;
	}
	

	/**
	 * Guess as what the relationship function on the parent model will be
	 * that points back to the model for this controller by using THIS
	 * controller's name.
	 * @return string ex: "slides" if this the slides controller
	 */
	public function deduceParentRelationship() {
		
		// The relationship is generally a plural form of the model name.
		// For instance, if Article has-many SuperSlide, then there will be a "superSlides"
		// relationship on Article.
		 // Remove namespaces
		$model = $this->getClassName($this->controller->model());
		$relationship = Str::plural(lcfirst($model));
		
		// Verify that it exists
		if (!method_exists($this->controller->parentModel(), $relationship)) {
			throw new Exception('Parent relationship missing, looking for: '.$relationship);
		}
		return $relationship;
	}
	
	/**
	 * Guess at what the child relationship name is, which is on the active controller,
	 * pointing back to the parent.  This is typically the same
	 * as the parent model.  For instance, Post has many Image.  Image will have
	 * a function named "post" for it's relationship
	 */
	public function deduceChildRelationship() {
		
		// If one to many, it will be singular
		$parent_model = $this->getClassName($this->controller->parentModel());
		$relationship = lcfirst($parent_model);
		
		// If it doesn't exist, try the plural version, which would be correct
		// for a many to many relationship
		if (!method_exists($this->controller->model(), $relationship)) {
			$relationship = Str::plural($relationship);
			if (!method_exists($this->controller->model(), $relationship)) {
				throw new Exception('Child relationship missing, looking for '.$relationship);
			}
		}
		return $relationship;
	}
	
	/**
	 * Get the parent controller's id from the route or return false
	 * @return mixed An id or false
	 */
	public function parentId() {
		if (!$this->requestIsChild()) return false;
		return $this->wildcard->detectParentId();
	}
	
	/**
	 * Take a model fullly namespaced class name and get just the class
	 * @param string $class ex: Bkwld\Decoy\Models\Admin
	 * @return string ex: Admin
	 */
	private function getClassName($class) {
		if (preg_match('#[a-z-]+$#i', $class, $matches)) return $matches[0];
		throw new Exception('Class name could not be found: '. $class);
	}
	
}