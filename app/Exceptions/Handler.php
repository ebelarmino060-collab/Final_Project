<?php

namespace App\Exceptions;

use App\Traits\ApiResponses;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Turns every exception raised by an API request into the same JSON error format.
 */
class Handler extends ExceptionHandler
{
    // Gives the handler the error() helper from the ApiResponses trait
    use ApiResponses;

    /**
     * Fields never flashed to the session when validation fails.
     *
     * @var list<string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Which method builds the error list for which exception class.
     *
     * @var array<class-string<Throwable>, string>
     */
    protected array $apiHandlers = [
        ValidationException::class => 'handleValidation',
        ModelNotFoundException::class => 'handleModelNotFound',
        AuthenticationException::class => 'handleAuthentication',
        NotFoundHttpException::class => 'handleNotFoundHttp',
    ];

    /**
     * Laravel calls this for every exception. API requests get the standard JSON error list;
     * every other request (the web pages) keeps Laravel's default rendering.
     *
     * @param  Request  $request
     */
    public function render($request, Throwable $exception): Response
    {
        if (! $request->is('api/*') && ! $request->expectsJson()) {
            return parent::render($request, $exception);
        }

        $className = get_class($exception);

        // A known exception: build its error list with the matching method
        if (array_key_exists($className, $this->apiHandlers)) {
            $method = $this->apiHandlers[$className];

            return $this->error($this->$method($exception));
        }

        // Anything else: use the exception's own status (for example 405), or 500
        $status = $exception instanceof HttpExceptionInterface
            ? $exception->getStatusCode()
            : 500;

        $debug = (bool) config('app.debug');

        return $this->error([
            [
                'type' => class_basename($className),
                'status' => $status,
                // Only reveal the real message, file and line while APP_DEBUG is true
                'message' => $debug ? $exception->getMessage() : $this->genericMessage($status),
                'source' => $debug ? 'Line '.$exception->getLine().': '.$exception->getFile() : '',
            ],
        ]);
    }

    /**
     * 422: one error object per invalid field message.
     *
     * @return list<array{status: int, message: string, source: string}>
     */
    private function handleValidation(ValidationException $exception): array
    {
        $errors = [];

        foreach ($exception->errors() as $key => $messages) {
            foreach ($messages as $message) {
                $errors[] = [
                    'status' => 422,
                    'message' => $message,
                    'source' => $key,
                ];
            }
        }

        return $errors;
    }

    /**
     * 404: a model was not found in the database.
     *
     * @return list<array{status: int, message: string, source: string}>
     */
    private function handleModelNotFound(ModelNotFoundException $exception): array
    {
        return [
            [
                'status' => 404,
                'message' => 'The resource cannot be found.',
                'source' => $exception->getModel(),
            ],
        ];
    }

    /**
     * 404: the URL does not exist (or route model binding failed).
     *
     * @return list<array{status: int, message: string, source: string}>
     */
    private function handleNotFoundHttp(NotFoundHttpException $exception): array
    {
        $previous = $exception->getPrevious();

        // When binding failed, the original exception tells us which model is missing
        $source = $previous instanceof ModelNotFoundException ? $previous->getModel() : 'Route';

        return [
            [
                'status' => 404,
                'message' => 'The resource cannot be found.',
                'source' => $source,
            ],
        ];
    }

    /**
     * 401: missing, invalid, expired or revoked token.
     *
     * @return list<array{status: int, message: string, source: string}>
     */
    private function handleAuthentication(AuthenticationException $exception): array
    {
        return [
            [
                'status' => 401,
                'message' => 'Unauthenticated',
                'source' => '',
            ],
        ];
    }

    /**
     * A safe message for production: the standard status text for HTTP errors
     * (for example "Method Not Allowed"), and "Server error" for everything else.
     */
    private function genericMessage(int $status): string
    {
        return $status >= 500 ? 'Server error' : (Response::$statusTexts[$status] ?? 'Error');
    }
}
