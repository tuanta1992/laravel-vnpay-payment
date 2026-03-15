<?php

namespace VNPayPayment\Exceptions;

/**
 * VNPayResponseException
 *
 * Thrown when VNPay returns an error response
 * Includes response code and full response data
 */
class VNPayResponseException extends VNPayException
{
    /**
     * VNPay response code
     *
     * @var string
     */
    protected string $responseCode;

    /**
     * Full response data
     *
     * @var array
     */
    protected array $responseData;

    /**
     * Constructor
     *
     * @param string $responseCode VNPay response code
     * @param string $message Error message
     * @param array $responseData Full response data from VNPay
     * @param array $context Additional context data
     */
    public function __construct(
        string $responseCode,
        string $message,
        array $responseData = [],
        array $context = []
    ) {
        $this->responseCode = $responseCode;
        $this->responseData = $responseData;

        $context['response_code'] = $responseCode;
        $context['response_data'] = $responseData;

        parent::__construct($message, 0, $context);
    }

    /**
     * Get VNPay response code
     *
     * @return string
     */
    public function getResponseCode(): string
    {
        return $this->responseCode;
    }

    /**
     * Get full response data
     *
     * @return array
     */
    public function getResponseData(): array
    {
        return $this->responseData;
    }

    /**
     * Check if response code indicates success
     *
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->responseCode === '00';
    }
}
