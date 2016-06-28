<?php

$factory->define(Frasmage\Mediable\Media::class, function (Faker\Generator $faker) {
    $types = config('mediable.types');
    $type = $faker->randomElement(array_keys($types));

    return [
        'disk' => 'tmp',
        'directory' => implode('/', $faker->words($faker->randomDigit)),
        'filename' => $faker->word,
        'extension' => $faker->randomElement($types[$type]['extensions']),
        'mime_type' => $faker->randomElement($types[$type]['mime_type']),
        'type' => $type,
        'size' => $faker->randomNumber(),
    ];
});
