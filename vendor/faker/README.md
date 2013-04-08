Faker — A fake text generator
=============================

Generates ludicrous randomized text for functional testing, fuzz testing, or lorem ipsum-esque purposes.

This is more or less a direct PHP port of the Ruby Faker gem, which is more or less a direct port of the Perl Data::Faker from CPAN. Actually, it does a little more than that because I’m obsessive compulsive and possibly a little bit mad.

Install
-------

You can install Faker directly via the PEAR channel:

```
$ pear channel-discover maetl.github.com/faker
$ pear install faker/Faker-beta
```

If all went well, the Faker library is now in your PHP include path and a command line tool has been installed, providing a shortcut syntax for accessing the Faker API. Run the following to try it out:

```
$ faker
```

Overview
--------

The Faker object itself is just a wrapper to hierarchically access the directory of fake objects and call methods on them to generate data.

The following Fake objects are installed by default:

- Address
- Color
- Company
- Internet
- Person
- Product

In development:

- Number
- Text

Example Usage
-------------

```
$ faker color.hex
$ faker person.name
$ faker address.city
$ faker product.price 100 200
```

Running these commands is equivalent to the following PHP:

```php
require_once 'Faker.php';

$faker = new Faker();

echo $faker->color->hex();
echo $faker->person->name();
echo $faker->address->city();
echo $faker->product->price(100, 200);
```
