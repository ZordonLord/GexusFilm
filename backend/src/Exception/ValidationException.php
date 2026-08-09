<?php

declare(strict_types=1);

namespace App\Exception;

final class ValidationException extends \Exception
{
    /**
     * @param array<int, array{field: string, issue: string}> $details
     */
    public function __construct(string $message, public readonly array $details = [])
    {
        parent::__construct($message);
    }
}
