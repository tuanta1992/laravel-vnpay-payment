<?php

namespace VNPayPayment\Exceptions;

/**
 * VNPayValidationException
 *
 * Thrown when VNPay parameter validation fails
 * Includes field name for targeted error handling
 */
class VNPayValidationException extends VNPayException
{
    /**
     * Field name that failed validation
     *
     * @var string
     */
    protected string $field;

    /**
     * Constructor
     *
     * @param string $field Field name that failed
     * @param string $message Error message
     * @param array $context Additional context data
     */
    public function __construct(
        string $field,
        string $message,
        array $context = []
    ) {
        $this->field = $field;
        $context['field'] = $field;
        parent::__construct($message, 0, $context);
    }

    /**
     * Get field name that failed validation
     *
     * @return string
     */
    public function getField(): string
    {
        return $this->field;
    }
}
