<?php

namespace VNPayPayment\Api;

abstract class BaseApi
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Thực thi API
     *
     * @param array $params
     * @return mixed
     */
    abstract public function execute(array $params): mixed;

    /**
     * Lấy URL theo môi trường
     *
     * @param string $type 'payment' hoặc 'api'
     * @return string
     */
    protected function getUrl(string $type = 'payment'): string
    {
        $environment = $this->config['environment'] ?? 'sandbox';
        return $this->config['urls'][$environment][$type] ?? '';
    }

    /**
     * Tạo secure hash theo HMAC SHA512
     *
     * @param string $data
     * @return string
     */
    protected function createSecureHash(string $data): string
    {
        return hash_hmac('sha512', $data, $this->config['hash_secret']);
    }

    /**
     * Sắp xếp params theo alphabet và tạo query string
     *
     * @param array $params
     * @param bool $encode
     * @return string
     */
    protected function buildQueryString(array $params, bool $encode = true): string
    {
        ksort($params);

        $parts = [];
        foreach ($params as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            if ($encode) {
                $parts[] = urlencode($key) . '=' . urlencode($value);
            } else {
                $parts[] = $key . '=' . $value;
            }
        }

        return implode('&', $parts);
    }

    /**
     * Tạo hash data để tính secure hash
     *
     * @param array $params
     * @return string
     */
    protected function buildHashData(array $params): string
    {
        ksort($params);

        $parts = [];
        foreach ($params as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $parts[] = $key . '=' . $value;
        }

        return implode('&', $parts);
    }

    /**
     * Generate random transaction reference
     *
     * @param int $length
     * @return string
     */
    protected function generateTxnRef(int $length = 8): string
    {
        return strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, $length));
    }

    /**
     * Format số tiền theo yêu cầu VNPay (nhân 100)
     *
     * @param float|int $amount
     * @return int
     */
    protected function formatAmount($amount): int
    {
        return (int) ($amount * 100);
    }

    /**
     * Parse số tiền từ VNPay (chia 100)
     *
     * @param int $amount
     * @return float
     */
    protected function parseAmount(int $amount): float
    {
        return $amount / 100;
    }

    /**
     * Format datetime theo định dạng YmdHis
     *
     * @param \DateTime|string|null $datetime
     * @return string
     */
    protected function formatDateTime($datetime = null): string
    {
        if ($datetime === null) {
            $datetime = now();
        } elseif (is_string($datetime)) {
            $datetime = new \DateTime($datetime);
        }

        return $datetime->format('YmdHis');
    }

    /**
     * Parse datetime từ string YmdHis
     *
     * @param string $datetime
     * @return \DateTime
     */
    protected function parseDateTime(string $datetime): \DateTime
    {
        return \DateTime::createFromFormat('YmdHis', $datetime);
    }

    /**
     * Lấy IP address của client
     *
     * @return string
     */
    protected function getIpAddress(): string
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        }
    }

    /**
     * Validate required params
     *
     * @param array $params
     * @param array $required
     * @throws \InvalidArgumentException
     */
    protected function validateRequired(array $params, array $required): void
    {
        foreach ($required as $key) {
            if (!isset($params[$key]) || $params[$key] === '') {
                throw new \InvalidArgumentException("Missing required parameter: {$key}");
            }
        }
    }
}