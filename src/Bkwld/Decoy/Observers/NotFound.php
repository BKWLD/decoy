<?php namespace Bkwld\Decoy\Observers;

// Deps
use Redirect;
use Bkwld\Decoy\Models\RedirectRule;

/**
 * Redirect on 404 using CRUDed redirect rules
 */
class NotFound {

	/**
	 * @var RedirectRule
	 */
	protected $model;

	/**
	 * Dependency injection
	 */
	public function __construct(RedirectRule $model) {
		$this->model = $model;
	}

	/**
	 * Redirect on 404 using CRUDed redirect rules
	 * 
	 * @return Illuminate\Http\RedirectResponse|void
	 */
	public function handle() {
		if ($rule = $this->model->matchUsingRequest()->first()) {
			return Redirect::to($rule->to, $rule->code);
		}
	}

}