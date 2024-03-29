<?php

namespace ImiDiPasswordProtectedPages\Exception;

use Shopware\Core\Framework\HttpException;

class UnauthorizedException extends HttpException
{
    public function __construct(
        protected int $statusCode,
        protected string $errorCode,
        string $message,
        array $parameters = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct($statusCode, $errorCode, $message, $parameters, $previous);
    }
}
