<?php

use App\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;

/**
 * Exposes the trait's protected helpers so they can be called from the tests.
 */
function apiResponder(): object
{
    return new class
    {
        use ApiResponses;

        public function callOk(string $message, mixed $data = null): JsonResponse
        {
            return $this->ok($message, $data);
        }

        public function callSuccess(string $message, mixed $data, int $statusCode): JsonResponse
        {
            return $this->success($message, $data, $statusCode);
        }

        public function callError(string $message, int $statusCode): JsonResponse
        {
            return $this->error($message, $statusCode);
        }
    };
}

test('ok returns 200 with data, message and status', function () {
    $response = apiResponder()->callOk('API is working');

    expect($response->getStatusCode())->toBe(200)
        ->and($response->getData(true))->toBe([
            'data' => null,
            'message' => 'API is working',
            'status' => 200,
        ]);
});

test('success sends the given status as the real HTTP status', function () {
    $response = apiResponder()->callSuccess('Created', ['id' => 1], 201);

    expect($response->getStatusCode())->toBe(201)
        ->and($response->getData(true))->toBe([
            'data' => ['id' => 1],
            'message' => 'Created',
            'status' => 201,
        ]);
});

test('error returns the message and status without a data key', function () {
    $response = apiResponder()->callError('Invalid credentials', 401);

    expect($response->getStatusCode())->toBe(401)
        ->and($response->getData(true))->toBe([
            'message' => 'Invalid credentials',
            'status' => 401,
        ]);
});
