<?php

declare(strict_types=1);

namespace Volcanic\Exceptions;

use Exception;
use Throwable;

class InvalidFieldException extends Exception
{
    public function __construct(string $field, string $operation, array $allowedFields, int $code = 400, ?Throwable $previous = null)
    {
        $message = $this->buildErrorMessage($field, $operation, $allowedFields);

        parent::__construct($message, $code, $previous);
    }

    private function buildErrorMessage(string $field, string $operation, array $allowedFields): string
    {
        if (in_array('*', $allowedFields, true)) {
            return "Field '{$field}' is not allowed for {$operation}. This API accepts any field due to wildcard (*) configuration, but '{$field}' may not exist on this model.";
        }

        if ($allowedFields === []) {
            return "Field '{$field}' is not allowed for {$operation}. No fields are configured as allowed for this operation.";
        }

        $allowedFieldsList = implode(', ', $allowedFields);

        return "Field '{$field}' is not allowed for {$operation}. Allowed fields are: {$allowedFieldsList}";
    }
}
