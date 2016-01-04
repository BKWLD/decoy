<?php namespace Bkwld\Decoy\Facades;
use Illuminate\Support\Facades\Facade;
class DecoyURL extends Facade {
	protected static function getFacadeAccessor() { return 'decoy.url'; }
}