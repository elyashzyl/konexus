<?php

namespace App\Exceptions;

use RuntimeException;

class ApiException extends RuntimeException
{
    /**
     * @param  array<string, mixed>|null  $errors
     */
    public function __construct(
        string $message = 'Request failed.',
        private readonly int $statusCode = 400,
        private readonly ?array $errors = null,
    ) {
        parent::__construct($message);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function errors(): ?array
    {
        return $this->errors;
    }

    public static function badRequest(string $message = 'Invalid request.', ?array $errors = null): self
    {
        return new self($message, 400, $errors);
    }

    public static function unauthorized(string $message = 'Unauthenticated.', ?array $errors = null): self
    {
        return new self($message, 401, $errors);
    }

    public static function forbidden(string $message = 'You are not authorized to perform this action.', ?array $errors = null): self
    {
        return new self($message, 403, $errors);
    }

    public static function notFound(string $message = 'Resource not found.', ?array $errors = null): self
    {
        return new self($message, 404, $errors);
    }

    public static function unprocessable(string $message = 'The given data was invalid.', ?array $errors = null): self
    {
        return new self($message, 422, $errors);
    }

    public static function conflict(string $message = 'The request conflicts with the current state.', ?array $errors = null): self
    {
        return new self($message, 409, $errors);
    }

    public static function tooManyRequests(string $message = 'Too many requests.', ?array $errors = null): self
    {
        return new self($message, 429, $errors);
    }

    public static function serverError(string $message = 'An unexpected error occurred.', ?array $errors = null): self
    {
        return new self($message, 500, $errors);
    }
}
