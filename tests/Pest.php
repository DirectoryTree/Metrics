<?php

use DirectoryTree\Metrics\Tests\User;

uses(DirectoryTree\Metrics\Tests\TestCase::class)->in(__DIR__);

function createUser(array $attributes = []): User
{
    return User::create([
        'name' => 'John',
        'email' => 'john@example.com',
        'password' => 'password',
        ...$attributes,
    ]);
}
