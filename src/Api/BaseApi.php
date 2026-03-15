<?php

namespace VNPayPayment\Api;

use VNPayPayment\VNPayConstants;
use VNPayPayment\Exceptions\VNPayValidationException;

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
     * Theo chuẩn VNPay - dùng urlencode cho key và value
     *
     * @param array $params
     * @return string
     */
    protected function buildHashData(array $params): string
    {
        ksort($params);

        $hashdata = "";
        $i = 0;
        foreach ($params as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            if ($i == 1) {
                $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashdata .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
        }

        return $hashdata;
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
     * @throws VNPayValidationException
     */
    protected function validateRequired(array $params, array $required): void
    {
        foreach ($required as $key) {
            if (!isset($params[$key]) || $params[$key] === '') {
                throw new VNPayValidationException(
                    $key,
                    "Missing required parameter: {$key}",
                    ['provided_keys' => array_keys($params)]
                );
            }
        }
    }

    /**
     * Validate direct VNPay parameters (vnp_ prefixed)
     *
     * @param array $params
     * @param array $required
     * @throws VNPayValidationException
     */
    protected function validateVnpParams(array $params, array $required): void
    {
        foreach ($required as $key) {
            if (!isset($params[$key]) || $params[$key] === '') {
                throw new VNPayValidationException(
                    $key,
                    "Missing required VNPay parameter: {$key}",
                    ['parameter_type' => 'direct', 'provided_keys' => array_keys($params)]
                );
            }
        }
    }

    /**
     * Validate VNPay amount format (must be integer in smallest currency unit)
     *
     * @param mixed $amount
     * @throws VNPayValidationException
     */
    protected function validateVnpAmount($amount): void
    {
        if (!is_int($amount) || $amount <= 0) {
            throw new VNPayValidationException(
                'vnp_Amount',
                "vnp_Amount must be a positive integer. Got: " . gettype($amount) . " = {$amount}",
                ['provided_type' => gettype($amount), 'provided_value' => $amount]
            );
        }

        // VNPay minimum is typically 10,000 VND = 1,000,000 in smallest unit
        if ($amount < VNPayConstants::MINIMUM_AMOUNT) {
            \Log::warning("vnp_Amount seems low: {$amount}. Expected format: amount * 100. Example: 100,000 VND = 10,000,000");
        }
    }

    /**
     * Remove Vietnamese accents for VNPay compatibility
     * Single source of truth for accent removal across all API classes
     *
     * @param string $str
     * @return string
     */
    protected function removeAccents(string $str): string
    {
        $accents = [
            'à', 'á', 'ả', 'ã', 'ạ', 'ă', 'ắ', 'ằ', 'ẳ', 'ẵ', 'ặ', 'â', 'ấ', 'ầ', 'ẩ', 'ẫ', 'ậ',
            'è', 'é', 'ẻ', 'ẽ', 'ẹ', 'ê', 'ế', 'ề', 'ể', 'ễ', 'ệ',
            'ì', 'í', 'ỉ', 'ĩ', 'ị',
            'ò', 'ó', 'ỏ', 'õ', 'ọ', 'ô', 'ố', 'ồ', 'ổ', 'ỗ', 'ộ', 'ơ', 'ớ', 'ờ', 'ở', 'ỡ', 'ợ',
            'ù', 'ú', 'ủ', 'ũ', 'ụ', 'ư', 'ứ', 'ừ', 'ử', 'ữ', 'ự',
            'ỳ', 'ý', 'ỷ', 'ỹ', 'ỵ',
            'đ',
            'À', 'Á', 'Ả', 'Ã', 'Ạ', 'Ă', 'Ắ', 'Ằ', 'Ẳ', 'Ẵ', 'Ặ', 'Â', 'Ấ', 'Ầ', 'Ẩ', 'Ẫ', 'Ậ',
            'È', 'É', 'Ẻ', 'Ẽ', 'Ẹ', 'Ê', 'Ế', 'Ề', 'Ể', 'Ễ', 'Ệ',
            'Ì', 'Í', 'Ỉ', 'Ĩ', 'Ị',
            'Ò', 'Ó', 'Ỏ', 'Õ', 'Ọ', 'Ô', 'Ố', 'Ồ', 'Ổ', 'Ỗ', 'Ộ', 'Ơ', 'Ớ', 'Ờ', 'Ở', 'Ỡ', 'Ợ',
            'Ù', 'Ú', 'Ủ', 'Ũ', 'Ụ', 'Ư', 'Ứ', 'Ừ', 'Ử', 'Ữ', 'Ự',
            'Ỳ', 'Ý', 'Ỷ', 'Ỹ', 'Ỵ',
            'Đ'
        ];

        $noAccents = [
            'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a',
            'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e',
            'i', 'i', 'i', 'i', 'i',
            'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o',
            'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u',
            'y', 'y', 'y', 'y', 'y',
            'd',
            'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A',
            'E', 'E', 'E', 'E', 'E', 'E', 'E', 'E', 'E', 'E', 'E',
            'I', 'I', 'I', 'I', 'I',
            'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O',
            'U', 'U', 'U', 'U', 'U', 'U', 'U', 'U', 'U', 'U', 'U',
            'Y', 'Y', 'Y', 'Y', 'Y',
            'D'
        ];

        return str_replace($accents, $noAccents, $str);
    }

    /**
     * Truncate order info to VNPay maximum length (255 chars)
     * Automatically cuts off if exceeds limit
     *
     * @param string $orderInfo
     * @param int|null $maxLength Maximum length (defaults to VNPayConstants::MAX_ORDER_INFO_LENGTH)
     * @return string
     */
    protected function truncateOrderInfo(string $orderInfo, ?int $maxLength = null): string
    {
        $maxLength = $maxLength ?? VNPayConstants::MAX_ORDER_INFO_LENGTH;

        if (strlen($orderInfo) > $maxLength) {
            return substr($orderInfo, 0, $maxLength);
        }

        return $orderInfo;
    }

    /**
     * Normalize parameter - support both vnp_ prefix and abstracted keys
     * Follows strategy: vnp_ prefix takes priority for dual-mode support
     *
     * @param array $params Parameters array
     * @param string $vnpKey VNPay parameter name (e.g., 'vnp_TxnRef')
     * @param string|null $abstractedKey Abstracted key (e.g., 'txn_ref'), null to skip
     * @param mixed $default Default value if not found
     * @return mixed
     */
    protected function normalizeParam(array $params, string $vnpKey, ?string $abstractedKey = null, $default = null)
    {
        // Try vnp_ prefix first (direct mode priority)
        if (isset($params[$vnpKey])) {
            return $params[$vnpKey];
        }

        // Try abstracted key if provided
        if ($abstractedKey !== null && isset($params[$abstractedKey])) {
            return $params[$abstractedKey];
        }

        return $default;
    }

    /**
     * Normalize multiple parameters at once
     * Reduces repetitive dual-mode parameter handling across API classes
     *
     * @param array $params Input parameters
     * @param array $mappings Mapping of vnp_ keys to abstracted keys ['vnp_TxnRef' => 'txn_ref', ...]
     * @return array Normalized values keyed by vnp_ parameter names
     */
    protected function normalizeParams(array $params, array $mappings): array
    {
        $normalized = [];

        foreach ($mappings as $vnpKey => $abstractedKey) {
            $value = $this->normalizeParam($params, $vnpKey, $abstractedKey);
            if ($value !== null) {
                $normalized[$vnpKey] = $value;
            }
        }

        return $normalized;
    }

    /**
     * Apply optional fields from params to inputData
     * Eliminates repetitive if (!empty()) conditional blocks
     *
     * @param array $inputData Reference to the input data array
     * @param array $params Source parameters array
     * @param array $fields Array of field names to copy if not empty
     * @return array The modified inputData
     */
    protected function applyOptionalFields(array $inputData, array $params, array $fields): array
    {
        foreach ($fields as $field) {
            if (!empty($params[$field])) {
                $inputData[$field] = $params[$field];
            }
        }

        return $inputData;
    }
}