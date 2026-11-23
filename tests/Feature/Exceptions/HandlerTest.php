<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    $this->token = jwtFor(User::factory()->create());
});

it('returns 401 in the standard format without a token', function () {
    $this->getJson('/api/v1/tickets')
        ->assertUnauthorized()
        ->assertExactJson(['errors' => [['status' => 401, 'message' => 'Unauthenticated', 'source' => '']]]);
});

it('returns 404 with the model as the source for a missing ticket', function () {
    $this->withToken($this->token)
        ->getJson('/api/v1/tickets/999999')
        ->assertNotFound()
        ->assertExactJson(['errors' => [['status' => 404, 'message' => 'The resource cannot be found.', 'source' => 'App\Models\Ticket']]]);
});

it('returns 404 with Route as the source for an unknown URL', function () {
    $this->withToken($this->token)
        ->getJson('/api/v1/nothing')
        ->assertNotFound()
        ->assertJsonPath('errors.0.source', 'Route');
});

it('returns one 422 error object per validation problem', function () {
    $this->withToken($this->token)
        ->postJson('/api/v1/tickets', ['data' => ['attributes' => ['title' => 'Headphones']]])
        ->assertUnprocessable()
        ->assertJsonPath('errors.0.status', 422)
        ->assertJsonFragment(['status' => 422, 'message' => 'The data.attributes.description field is required.', 'source' => 'data.attributes.description']);
});

it('returns 405 in the standard format for a wrong method', function () {
    $this->withToken($this->token)
        ->deleteJson('/api/v1/auth/me')
        ->assertMethodNotAllowed()
        ->assertJsonPath('errors.0.status', 405)
        ->assertJsonPath('errors.0.type', 'MethodNotAllowedHttpException');
});

it('hides the message, file and line of an unexpected error when debug is off', function () {
    config(['app.debug' => false]);
    Route::get('api/v1/boom', fn () => throw new RuntimeException('database password is hunter2'));

    $this->getJson('/api/v1/boom')
        ->assertInternalServerError()
        ->assertExactJson(['errors' => [['type' => 'RuntimeException', 'status' => 500, 'message' => 'Server error', 'source' => '']]]);
});

it('shows the message and location of an unexpected error when debug is on', function () {
    config(['app.debug' => true]);
    Route::get('api/v1/boom', fn () => throw new RuntimeException('Something broke'));

    $this->getJson('/api/v1/boom')
        ->assertInternalServerError()
        ->assertJsonPath('errors.0.message', 'Something broke')
        ->assertJsonPath('errors.0.source', fn (string $source): bool => str_starts_with($source, 'Line '));
});

it('keeps the standard status text for HTTP errors when debug is off', function () {
    config(['app.debug' => false]);

    $this->withToken($this->token)
        ->deleteJson('/api/v1/auth/me')
        ->assertMethodNotAllowed()
        ->assertJsonPath('errors.0.message', 'Method Not Allowed');
});

it('keeps the default HTML error page for web routes', function () {
    $response = $this->get('/no-such-page')->assertNotFound();

    expect($response->headers->get('Content-Type'))->toContain('text/html');
});
