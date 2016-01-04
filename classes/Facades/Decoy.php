<?php namespace Bkwld\Decoy\Facades;
use Illuminate\Support\Facades\Facade;
class Decoy extends Facade {
	protected static function getFacadeAccessor() { return 'decoy'; }
}