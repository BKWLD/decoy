<?php

// Forwarding all requests on, this file will be deprecated in
// Decoy 3.0
HTML::macro('title', 'Decoy::title');
HTML::macro('bodyClass', 'Decoy::bodyClass');
HTML::macro('renderListColumn', 'Decoy::renderListColumn');
HTML::macro('imageUpload', 'Decoy::imageUpload');
HTML::macro('fileUpload', 'Decoy::fileUpload');
HTML::macro('belongsTo', 'Decoy::belongsTo');
HTML::macro('inputlessField', 'Decoy::inputlessField'); 
HTML::macro('date', 'Decoy::date');
HTML::macro('time', 'Decoy::time');
HTML::macro('datetime', 'Decoy::datetime');
HTML::macro('relative', 'DecoyURL::relative');
HTML::macro('controller', 'DecoyURL::action');
