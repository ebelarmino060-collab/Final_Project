<?php

use App\Models\User;
use App\Policies\V1\UserPolicy;

test('only a manager token may manage users', function (string $ability, bool $isManager, bool $allowed) {
    $user = User::factory()->create(['is_manager' => $isManager]);
    $target = User::factory()->create();
    auth('api')->setToken(jwtFor($user));

    $answer = $ability === 'store'
        ? (new UserPolicy)->store($user)
        : (new UserPolicy)->{$ability}($user, $target);

    expect($answer)->toBe($allowed);
})->with([
    'manager creates' => ['store', true, true],
    'regular user creates' => ['store', false, false],
    'manager updates' => ['update', true, true],
    'regular user updates' => ['update', false, false],
    'manager replaces' => ['replace', true, true],
    'regular user replaces' => ['replace', false, false],
    'manager deletes' => ['delete', true, true],
    'regular user deletes' => ['delete', false, false],
]);
