<?php

namespace App\Exceptions;

use Exception;

class OrderException extends Exception
{
    /**
     * Создать новый экземпляр исключения заказа.
     *
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     * @return void
     */
    public function __construct($message = 'Ошибка при работе с заказом', $code = 422, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}