<?php

namespace App\Exceptions;

use Exception;

class DataTransformationException extends Exception
{
    public function __construct(string $modelClass, string $dataClass, string $message = '')
    {
        $fullMessage = "Error transforming {$modelClass} to {$dataClass}: {$message}";
        parent::__construct($fullMessage);
    }
}