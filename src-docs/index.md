## Compatibility

Decoy is tested to support:

- Latest Chrome (recommended)
- Latest Firefox
- Latest Safari
- IE 10+
- iOS 8 Safari on iPhone and iPad
- Latest Android Chrome

## Version history

See the [Github "Releases"](https://github.com/BKWLD/decoy/releases) history

## Installation


1. `composer require bkwld/decoy`
2. Add `Bkwld\Decoy\ServiceProvider::class` to `providers` in your Laravel's app config file.
3. Add these aliases to the `aliases` in your Laravel's app config file:
	```php
	'Decoy' => Bkwld\Decoy\Facades\Decoy::class,
	'DecoyURL' => Bkwld\Decoy\Facades\DecoyURL::class,
	```
4. Publish the migrations, config files, and public assets by running `php artisan vendor:publish --provider="Bkwld\Decoy\ServiceProvider"`
5. Run the migrations by running `php artisan migrate`
