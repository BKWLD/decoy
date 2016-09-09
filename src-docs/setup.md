## Compatibility

Decoy is tested to support:

- Latest Chrome (recommended)
- Latest Firefox
- Latest Safari
- IE 9-11
- iOS 8 Safari on iPhone and iPad
- Latest Android Chrome

## Version history

See the [Github "Releases"](https://github.com/BKWLD/decoy/releases) history

## Installation

Decoy expects to be installed ontop of [Camo](https://github.com/BKWLD/camo).  In particular, Decoy has dependencies that are part of Camo's dependency list.  For instance, there are some expectating on the version of Compass that is used.

If you **are** installing outside of Camo, here are some steps to get you started.

1. Add `"bkwld/decoy": "~5.0",` to your composer.json and install.  This reflects the latest stable branch.
2. Edit the app config file as follows:

		<?php
		'providers' => [
			Bkwld\Decoy\ServiceProvider::class
		], 'aliases' => [
			'Decoy' => Bkwld\Decoy\Facades\Decoy::class,
			'DecoyURL' => Bkwld\Decoy\Facades\DecoyURL::class,
		],

3. Run `php artisan vendor:publish --provider="Bkwld\Decoy\ServiceProvider"`
4. Run `php artisan migrate`


## Contributing

- The `master` branch represents what will be come the next **minor** release.

- A small, low-risk feature for an actively developed project should be created in a feature branch (based on the latest version-branch) and then merged into both the version-branch and master.

- A riskier feature should be worked on in a feature branch and then moved into master.  When it's finished, it can be come part of the next minor version release.  This git command gives you a nice view into commits that are new on master versus the most recent version (replace `{branch}` with the latest versioned-branch):

		bash git log --graph --pretty=format:'%Cred%h%Creset -%C(yellow)%d%Creset %s %Cgreen(%cr)%Creset' --abbrev-commit --date=relative {branch}..master
