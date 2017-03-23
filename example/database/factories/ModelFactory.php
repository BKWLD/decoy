<?php

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/

$factory->define(App\User::class, function (Faker\Generator $faker) {
    return [
        'name' => $faker->name,
        'email' => $faker->safeEmail,
        'password' => bcrypt(str_random(10)),
        'remember_token' => str_random(10),
    ];
});

$factory->define(App\Article::class, function (Faker\Generator $faker) {
    return [
        'title' => $faker->name,
        'body' => 'Body',
        'category' => 'first',
        'public' => 1,
        'date' => $faker->date(),
    ];
});

$factory->define(App\Tag::class, function (Faker\Generator $faker) {
    return [
        'name' => $faker->word,
    ];
});

$factory->define(App\Slide::class, function (Faker\Generator $faker) {
    return [
        'title' => $faker->word,
    ];
});

$factory->define(Bkwld\Decoy\Models\Element::class, function (Faker\Generator $faker) {
    return [
        'key' => 'homepage.marquee.title',
        'type' => 'text',
        'value' => 'test',
        'locale' => 'en',
    ];
});

$factory->define(Bkwld\Decoy\Models\RedirectRule::class, function (Faker\Generator $faker) {
    return [
        'from' => 'test',
        'to' => '/redirected',
        'code' => 301,
        'label' => $faker->word,
    ];
});
