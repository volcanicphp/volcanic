<?php

declare(strict_types=1);

namespace Volcanic\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class InvalidParameterException extends Exception
{
    public function __construct(string $parameter, string $expectedType, mixed $actualValue, int $code = 404, ?Throwable $previous = null)
    {
        $message = $this->buildErrorMessage($parameter, $expectedType, $actualValue);

        parent::__construct($message, $code, $previous);
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render(Request $request): JsonResponse
    {
        $data = [
            'message' => $this->getMessage(),
        ];

        if (config('app.debug')) {
            $data['exception'] = static::class;
            $data['file'] = $this->getFile();
            $data['line'] = $this->getLine();
            $data['trace'] = $this->getTrace();
        }

        return new JsonResponse($data, $this->getCode());
    }

    private function buildErrorMessage(string $parameter, string $expectedType, mixed $actualValue): string
    {
        $actualType = gettype($actualValue);
        $actualValueString = is_string($actualValue) ? "'{$actualValue}'" : (string) $actualValue;

        return "Invalid parameter '{$parameter}'. Expected {$expectedType}, but received {$actualType} {$actualValueString}.";
    }
}
