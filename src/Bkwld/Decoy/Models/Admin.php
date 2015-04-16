<?php namespace Bkwld\Decoy\Models;

// Deps
use DecoyURL;
use HTML;
use Illuminate\Auth\UserTrait;
use Illuminate\Auth\UserInterface;
use Illuminate\Auth\Reminders\RemindableTrait;
use Illuminate\Auth\Reminders\RemindableInterface;
use URL;

class Admin extends Base implements UserInterface, RemindableInterface {
	use UserTrait, RemindableTrait;

	/**
	 * Validation rules
	 * 
	 * @var array
	 */
	public static $rules = [
		'first_name' => 'required',
		'last_name' => 'required',
		'image' => 'image',
		'email' => 'required|email|unique:admins,email',
		'password' => 'required',
		'confirm_password' => 'sometimes|required_with:password|same:password',
	];

	/**
	 * Orders instances of this model in the admin
	 * 
	 * @param  Illuminate\Database\Query\Builder $query
	 * @return void
	 */
	public function scopeOrdered($query) {
		$query->orderBy('last_name')->orderBy('first_name');
	}

	/**
	 * Produce the title for the list view
	 * 
	 * @return string
	 */
	public function title() {
		$image = $this->croppa(40,40) ?: HTML::gravatar($this->email);
		return "<img src='$image' class='gravatar'/> 
			{$this->first_name} {$this->last_name}";
	}

	/**
	 * Show a badge if the user is the currently logged in
	 * 
	 * @return string
	 */
	public function statuses() {
		$html ='';
		
		// If row is you
		if ($this->id == app('decoy.auth')->user()->id) {
			$html .= '<span class="label label-info">You</span>';
		}

		// If row is disabled
		if ($this->disabled()) {
			$html .= '<a href="'.URL::to(DecoyURL::relative('enable', $this->id)).'" class="label label-warning js-tooltip" title="Click to enable login">Disabled</a>';
		}

		// Return HTML
		return $html;
	}

	/**
	 * Check if admin is banned
	 * 
	 * @return boolean true if banned
	 */
	public function disabled() {
		return !$this->active;
	}

}