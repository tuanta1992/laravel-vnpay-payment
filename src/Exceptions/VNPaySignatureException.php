<?php

namespace VNPayPayment\Exceptions;

/**
 * VNPaySignatureException
 *
 * Thrown when signature verification fails
 * Invalid checksum or hash mismatch
 */
class VNPaySignatureException extends VNPayException
{
    /**
     * Expected signature
     *
     * @var string
     */
    protected string $expectedSignature;

    /**
     * Received signature
     *
     * @var string
     */
    protected string $receivedSignature;

    /**
     * Constructor
     *
     * @param string $message Error message
     * @param string $expectedSignature Expected signature
     * @param string $receivedSignature Received signature
     * @param array $context Additional context data
     */
    public function __construct(
        string $message,
        string $expectedSignature = '',
        string $receivedSignature = '',
        array $context = []
    ) {
        $this->expectedSignature = $expectedSignature;
        $this->receivedSignature = $receivedSignature;

        $context['expected_signature'] = substr($expectedSignature, 0, 10) . '...';
        $context['received_signature'] = substr($receivedSignature, 0, 10) . '...';
        $context['signature_match'] = $expectedSignature === $receivedSignature;

        parent::__construct($message, 0, $context);
    }

    /**
     * Get expected signature
     *
     * @return string
     */
    public function getExpectedSignature(): string
    {
        return $this->expectedSignature;
    }

    /**
     * Get received signature
     *
     * @return string
     */
    public function getReceivedSignature(): string
    {
        return $this->receivedSignature;
    }

    /**
     * Check if signatures match
     *
     * @return bool
     */
    public function signaturesMatch(): bool
    {
        return $this->expectedSignature === $this->receivedSignature;
    }
}
