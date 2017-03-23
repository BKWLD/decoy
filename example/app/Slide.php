<?php namespace App;
use Bkwld\Decoy\Models\Base;

class Slide extends Base {

	/**
	 * Validation rules
	 *
	 * @var array
	 */
	public static $rules = [
		'title' => 'required',
	];

	/**
	 * List of all relationships
	 *
	 * @return Illuminate\Database\Eloquent\Relations\Relation
	 */
	public function article()
    {
    	return $this->belongsTo('App\Article');
    }

}
