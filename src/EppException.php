<?php

namespace EppClient;

use Exception;

class EppException extends Exception
{
    public function __construct(?string $message = null, ?int $code = 0, mixed $previous = null)
    {
        parent::__construct(message: $message, code: $code, previous: $previous);
    }
}
