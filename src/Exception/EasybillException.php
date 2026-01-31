<?php

declare(strict_types=1);

namespace Dkd\EasybillClient\Exception;

use Exception;

/**
 * Base exception for Easybill errors.
 */
class EasybillException extends Exception
{
    public function __construct(
        string $message,
        public readonly ?int $statusCode = null,
        public readonly ?array $response = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }
}
