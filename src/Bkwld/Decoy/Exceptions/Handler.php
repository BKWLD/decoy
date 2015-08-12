<?php namespace Bkwld\Decoy\Exceptions;

// Deps
use App\Exceptions\Handler as AppHandler;
use Bkwld\Decoy\Models\RedirectRule;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Session\TokenMismatchException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Subclass the App's handler to add custom handling of various exceptions
 */
class Handler extends AppHandler {

	/**
	 * @var RedirectRule
	 */
	protected $redirector;

	/**
	 * DI
	 *
	 * @param  \Psr\Log\LoggerInterface  $log
	 * @return void
	 */
	public function __construct(LoggerInterface $log, RedirectRule $redirector) {
		parent::__construct($log);
		$this->redirector = $redirector;
	}

	/**
	 * Render an exception into an HTTP response.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Exception  $e
	 * @return \Illuminate\Http\Response
	 */
	public function render($request, Exception $e) {

		// Check for custom handling
		if ($response = $this->handle404s($e)) return $response;
		if ($response = $this->handleCSRF($e)) return $response;
		if ($response = $this->handleValidation($e)) return $response;

		// Allow the app to continue processing
		return parent::render($request, $e);
	}

	/**
	 * If a 404 exception, check if there is a redirect rule
	 *
	 * @param  \Exception  $e
	 * @return \Illuminate\Http\RedirectResponse
	 */
	protected function handle404s(Exception $e) {

		// Check for right exception
		if (!is_a($e, ModelNotFoundException::class) 
			&& !is_a($e, NotFoundHttpException::class)) return;

		// Check for 404
		if ($rule = $this->redirector->matchUsingRequest()->first()) {
			return redirect($rule->to, $rule->code);
		}
	}

	/**
	 * If a CSRF invalid exception, log the user out
	 *
	 * @param  \Exception  $e
	 * @return \Illuminate\Http\RedirectResponse
	 */
	protected function handleCSRF(Exception $e) {
		if (!is_a($e, TokenMismatchException::class)) return;
		return app('decoy.acl_fail');
	}

	/**
	 * Redirect users to the previous page with validation errors
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Exception  $e
	 * @return \Illuminate\Http\Response
	 */
	protected function handleValidation($request, Exception $e) {
		if (!is_a($e, ValidationFail::class)) return;

		// Log validation errors so Reporter will output them
		// if (Config::get('app.debug')) Log::debug(print_r($e->validation->messages(), true));
		
		// Respond
		if ($request->ajax()) {
			return response()->json($e->validation->messages(), 400);
		} else {
			return back()->withInput()->withErrors($e->validation);
		}

	}

} 

