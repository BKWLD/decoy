<?php

return [

    // The directory for the admin
    'dir' => 'admin',

    // The layout to use
    'layout' => 'decoy::layouts.default',

    // Auth guard and policy to use
    'guard'  => 'decoy',
    'policy' => 'Bkwld\Decoy\Auth\Policy@check',

    // Use a password input field for admins
    'obscure_admin_password' => false,

    // Mail FROM info
    'mail_from_name'    => 'Site Admin',
    'mail_from_address' => 'postmaster@'.(app()->runningInConsole() ?
        'locahost' : parse_url(url()->current(), PHP_URL_HOST)),

    // Allow regex in redirect rules
    'allow_regex_in_redirects' => false,

    // Register routes automatically in ServiceProvider->boot().  You might set
    // this to false if the App needed to register some /admin routes and didn't
    // want them to get trampled by the Decoy wildcard capture.
    'register_routes' => true,

    // Set up the default stylesheet and script tags
    'stylesheet' => '/assets/decoy/index.min.css',
    'script' => '/assets/decoy/index.min.js',

];
