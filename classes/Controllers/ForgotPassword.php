<?php

namespace Bkwld\Decoy\Controllers;

use Auth;
use Decoy;
use Former;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Bkwld\Decoy\Models\Admin;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;

class ForgotPassword extends Base
{

    use SendsPasswordResetEmails;

    /**
     * Display the form to request a password reset link.
     *
     * @return \Illuminate\Http\Response
     */
    public function showLinkRequestForm()
    {
        // Pass validation rules
        Former::withRules([
            'email' => 'required|email',
        ]);

        // Set the breadcrumbs
        app('decoy.breadcrumbs')->set([
            route('decoy::account@login') => 'Login',
            url()->current() => 'Forgot Password',
        ]);

        // Show the page
        $this->title = 'Forgot Password';
        $this->description = 'You know the drill.';

        return $this->populateView('decoy::account.forgot');
    }
}
