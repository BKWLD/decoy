Slugs are auto created from returned by `Bkwld\Decoy\Model\Base::getAdminTitleAttribute()`. Your model should have a validation rule like:

	'slug' => 'alpha_dash'

As long as there is a validation rule with a key of `slug`, Decoy will use cviebrock/eloquent-sluggable to create a slug using rules defined in the base model.
