<?php

namespace App\Exceptions;

use Exception;

class InsufficientStockException extends Exception
{
    /**
     * Создать новый экземпляр исключения недостаточности товара на складе.
     *
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     * @return void
     */
    public function __construct($message = 'Недостаточно товара на складе', $code = 422, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}