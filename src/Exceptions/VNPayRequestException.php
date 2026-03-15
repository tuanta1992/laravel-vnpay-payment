<?php

namespace VNPayPayment\Exceptions;

/**
 * VNPayRequestException
 *
 * Thrown when HTTP request to VNPay API fails
 * Network errors, timeouts, connection issues, etc.
 */
class VNPayRequestException extends VNPayException
{
    /**
     * Request URL
     *
     * @var string
     */
    protected string $url;

    /**
     * HTTP method
     *
     * @var string
     */
    protected string $method;

    /**
     * Request attempts
     *
     * @var int
     */
    protected int $attempts = 1;

    /**
     * Constructor
     *
     * @param string $message Error message
     * @param string $url Request URL
     * @param string $method HTTP method (GET, POST, etc.)
     * @param int $attempts Number of retry attempts
     * @param array $context Additional context data
     * @param \Throwable|null $previous Previous exception
     */
    public function __construct(
        string $message,
        string $url = '',
        string $method = 'POST',
        int $attempts = 1,
        array $context = [],
        \Throwable $previous = null
    ) {
        $this->url = $url;
        $this->method = $method;
        $this->attempts = $attempts;

        $context['url'] = $url;
        $context['method'] = $method;
        $context['attempts'] = $attempts;

        parent::__construct($message, 0, $context, $previous);
    }

    /**
     * Get request URL
     *
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Get HTTP method
     *
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Get number of retry attempts
     *
     * @return int
     */
    public function getAttempts(): int
    {
        return $this->attempts;
    }
}
