<?php

namespace VNPayPayment\Exceptions;

/**
 * Base VNPayException
 *
 * Base exception class for all VNPay-related errors
 * Includes context data for better debugging
 */
class VNPayException extends \Exception
{
    /**
     * Context data for debugging
     *
     * @var array
     */
    protected array $context = [];

    /**
     * Constructor
     *
     * @param string $message Error message
     * @param int $code Error code
     * @param array $context Additional context data
     * @param \Throwable|null $previous Previous exception
     */
    public function __construct(
        string $message = "",
        int $code = 0,
        array $context = [],
        \Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * Get context data
     *
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Get error as array for logging
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'exception' => class_basename($this),
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'context' => $this->context,
            'file' => $this->getFile(),
            'line' => $this->getLine(),
        ];
    }
}
