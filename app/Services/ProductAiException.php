<?php

namespace App\Services;

use RuntimeException;

class ProductAiException extends RuntimeException
{
    public function __construct(string $message, public readonly int $httpStatus)
    {
        parent::__construct($message);
    }
}
