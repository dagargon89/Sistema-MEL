<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Error de dominio con código HTTP del doc 05 §1.3. Los Services la lanzan y el
 * BaseApiController la traduce al envelope de error. Espeja el `err()` del mock.
 */
final class ApiException extends RuntimeException
{
    /** @var array<string, string> */
    private array $errors;

    private int $statusCode;

    /** @param array<string, string> $errors */
    public function __construct(int $statusCode, string $message, array $errors = [])
    {
        parent::__construct($message);
        $this->statusCode = $statusCode;
        $this->errors     = $errors;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /** @return array<string, string> */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /** @param array<string, string> $errors */
    public static function unprocessable(string $message, array $errors = []): self
    {
        return new self(422, $message, $errors);
    }

    public static function conflict(string $message): self
    {
        return new self(409, $message);
    }

    public static function forbidden(string $message): self
    {
        return new self(403, $message);
    }

    public static function notFound(string $message): self
    {
        return new self(404, $message);
    }
}
