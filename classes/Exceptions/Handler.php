<?php

namespace Bkwld\Decoy\Exceptions;

use Exception as BaseException;
use Bkwld\Decoy\Models\RedirectRule;
use App\Exceptions\Handler as AppHandler;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Subclass the App's handler to add custom handling of various exceptions
 */
class Handler extends AppHandler
{
    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception                $e
     * @return \Illuminate\Http\Response
     */
    public function render($request, BaseException $e)
    {
        // Check for custom handling
        if ($response = $this->handle404s($request, $e)) {
            return $response;
        }

        if ($response = $this->handleCSRF($e)) {
            return $response;
        }

        if ($response = $this->handleValidation($request, $e)) {
            return $response;
        }

        // Allow the app to continue processing
        return parent::render($request, $e);
    }

    /**
     * If a 404 exception, check if there is a redirect rule.  Or return a simple
     * header if an AJAX request.
     *
     * @param  \Illuminate\Http\Request          $request
     * @param  \Exception                        $e
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function handle404s($request, BaseException $e)
    {
        // Check for right exception
        if (!is_a($e, ModelNotFoundException::class) && !is_a($e, NotFoundHttpException::class)) {
            return;
        }

        // Check for a valid redirect
        if ($rule = RedirectRule::matchUsingRequest()->first()) {
            return redirect($rule->to, $rule->code);
        }

        // Return header only on AJAX
        if ($request->ajax()) {
            return response(null, 404);
        }
    }

    /**
     * If a CSRF invalid exception, log the user out
     *
     * @param  \Exception                        $e
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function handleCSRF(BaseException $e)
    {
        if (!is_a($e, TokenMismatchException::class)) {
            return;
        }

        return app('decoy.acl_fail');
    }

    /**
     * Redirect users to the previous page with validation errors
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception                $e
     * @return \Illuminate\Http\Response
     */
    protected function handleValidation($request, BaseException $e)
    {
        if (!is_a($e, ValidationFail::class)) {
            return;
        }

        // Log validation errors so Reporter will output them
        // if (Config::get('app.debug')) Log::debug(print_r($e->validation->messages(), true));

        // Respond
        if ($request->ajax()) {
            return response()->json($e->validation->messages(), 400);
        }

        return back()->withInput()->withErrors($e->validation);
    }
}
