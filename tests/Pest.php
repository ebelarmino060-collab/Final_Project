<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPUnit\Framework\Assert;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * Issue a JWT for the given user, exactly as login would.
 * The cached guards are reset so the next request authenticates this user, not a previous one.
 */
function jwtFor(User $user): string
{
    app('auth')->forgetGuards();

    return JWTAuth::fromUser($user);
}

/**
 * Build a JSON:API ticket request body. Pass null as a value to leave that key out.
 *
 * @param  array<string, mixed>  $attributes
 * @param  array<string, int|null>  $relationships  e.g. ['author' => 5, 'assignee' => 6]
 * @return array{data: array<string, mixed>}
 */
function ticketPayload(array $attributes = [], array $relationships = []): array
{
    $data = [
        'type' => 'ticket',
        'attributes' => array_merge([
            'title' => 'Printer offline',
            'description' => 'The second floor printer does not respond.',
            'status' => 'open',
            'priority' => 'high',
        ], $attributes),
    ];

    $data['attributes'] = array_filter($data['attributes'], fn (mixed $value): bool => $value !== null);

    foreach ($relationships as $name => $id) {
        $data['relationships'][$name] = ['data' => ['id' => $id]];
    }

    return ['data' => $data];
}

/*
| The API returns validation errors in its own format (Lesson 25):
| {"errors": [{"status": 422, "message": "...", "source": "data.attributes.title"}]}
| assertApiValidationErrors() accepts the same arguments as assertJsonValidationErrors():
| a list of fields, or field => expected message.
*/
TestResponse::macro('assertApiValidationErrors', function (array $expected): TestResponse {
    /** @var TestResponse $this */
    $errors = collect($this->json('errors'));

    foreach ($expected as $key => $message) {
        $field = is_int($key) ? $message : $key;
        $fieldErrors = $errors->where('source', $field);

        Assert::assertNotEmpty($fieldErrors, "No validation error for [{$field}]. Errors: ".$errors->toJson());
        Assert::assertSame([422], $fieldErrors->pluck('status')->unique()->values()->all());

        if (! is_int($key)) {
            Assert::assertContains($message, $fieldErrors->pluck('message')->all());
        }
    }

    return $this;
});
