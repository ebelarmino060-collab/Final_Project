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

test('error with a list of errors uses the status of the first error', function () {
    $responder = new class
    {
        use ApiResponses;

        /**
         * @param  list<array<string, mixed>>  $errors
         */
        public function callErrors(array $errors): JsonResponse
        {
            return $this->error($errors);
        }

        public function callNotAuthorized(string $message): JsonResponse
        {
            return $this->notAuthorized($message);
        }
    };

    $response = $responder->callErrors([['status' => 422, 'message' => 'Bad', 'source' => 'x']]);
    $forbidden = $responder->callNotAuthorized('No');

    expect($response->getStatusCode())->toBe(422)
        ->and($response->getData(true))->toBe(['errors' => [['status' => 422, 'message' => 'Bad', 'source' => 'x']]])
        ->and($forbidden->getStatusCode())->toBe(403)
        ->and($forbidden->getData(true))->toBe(['errors' => [['status' => 403, 'message' => 'No', 'source' => '']]]);
});

test('error falls back to 500 for an invalid status code', function () {
    $responder = new class
    {
        use ApiResponses;

        public function callErrors(): JsonResponse
        {
            return $this->error([['status' => 999, 'message' => 'Odd', 'source' => '']]);
        }
    };

    expect($responder->callErrors()->getStatusCode())->toBe(500);
});
