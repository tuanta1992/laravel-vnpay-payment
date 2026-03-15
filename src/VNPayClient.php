<?php

namespace VNPayPayment;

use VNPayPayment\Api\CreatePayment;
use VNPayPayment\Api\QueryTransaction;
use VNPayPayment\Api\RefundTransaction;
use VNPayPayment\Api\VerifyReturnUrl;
use VNPayPayment\Api\VerifyIpn;
use Illuminate\Support\Facades\Log;

class VNPayClient
{
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = !empty($config) ? $config : config('vnpay');
    }

    /**
     * Tạo URL thanh toán
     *
     * @param array $params
     * @return string
     */
    public function createPaymentUrl(array $params): string
    {
        $this->log('Creating payment URL', $params);

        $api = new CreatePayment($this->config);
        return $api->execute($params);
    }

    /**
     * Xác thực và xử lý response từ return URL
     *
     * @param array $params
     * @return array
     */
    public function verifyReturnUrl(array $params): array
    {
        $this->log('Verifying return URL', $params);

        $api = new VerifyReturnUrl($this->config);
        $result = $api->execute($params);

        $this->log('Return URL verified', $result);

        return $result;
    }

    /**
     * Xác thực và xử lý IPN notification
     *
     * @param array $params
     * @return array
     */
    public function verifyIpn(array $params): array
    {
        $api = new VerifyIpn($this->config);
        $result = $api->execute($params);
        $this->log('IPN verified', $result);

        return $result;
    }

    /**
     * Truy vấn thông tin giao dịch
     *
     * @param array $params
     * @return array
     * @throws \Exception
     */
    public function queryTransaction(array $params): array
    {
        $this->log('Querying transaction', $params);

        $api = new QueryTransaction($this->config);
        $result = $api->execute($params);

        $this->log('Transaction query result', $result);

        return $result;
    }

    /**
     * Hoàn tiền giao dịch
     *
     * @param array $params
     * @return array
     * @throws \Exception
     */
    public function refundTransaction(array $params): array
    {
        $this->log('Refunding transaction', $params);

        $api = new RefundTransaction($this->config);
        $result = $api->execute($params);

        $this->log('Refund result', $result);

        return $result;
    }

    /**
     * Lấy danh sách bank codes
     *
     * @return array
     */
    public function getBankCodes(): array
    {
        return $this->config['bank_codes'] ?? [];
    }

    /**
     * Lấy tên ngân hàng từ mã
     *
     * @param string $code
     * @return string|null
     */
    public function getBankName(string $code): ?string
    {
        return $this->config['bank_codes'][$code] ?? null;
    }

    /**
     * Lấy mô tả response code
     *
     * @param string $code
     * @return string|null
     */
    public function getResponseMessage(string $code): ?string
    {
        // First check config, then fall back to constants
        return $this->config['response_codes'][$code] ?? VNPayConstants::getResponseMessage($code);
    }

    /**
     * Lấy mô tả transaction status
     *
     * @param string $status
     * @return string|null
     */
    public function getTransactionStatusMessage(string $status): ?string
    {
        return $this->config['transaction_status'][$status] ?? null;
    }

    /**
     * Kiểm tra giao dịch có thành công không
     *
     * @param string $responseCode
     * @param string|null $transactionStatus
     * @return bool
     */
    public function isSuccess(string $responseCode, ?string $transactionStatus = null): bool
    {
        if ($responseCode !== '00') {
            return false;
        }

        if ($transactionStatus !== null && $transactionStatus !== '00') {
            return false;
        }

        return true;
    }

    /**
     * Kiểm tra IP có được phép gửi IPN không
     *
     * @param string $ip
     * @return bool
     */
    public function isAllowedIpnIp(string $ip): bool
    {
        // Nếu không bật verify IP thì cho phép tất cả
        if (!($this->config['ipn']['verify_ip'] ?? true)) {
            return true;
        }

        $environment = $this->config['environment'] ?? 'sandbox';
        $allowedIps = $this->config['ipn']['allowed_ips'][$environment] ?? [];

        return in_array($ip, $allowedIps, true);
    }

    /**
     * Lấy danh sách IP được phép theo môi trường hiện tại
     *
     * @return array
     */
    public function getAllowedIpnIps(): array
    {
        $environment = $this->config['environment'] ?? 'sandbox';
        return $this->config['ipn']['allowed_ips'][$environment] ?? [];
    }

    /**
     * Log khởi tạo payment (create payment URL)
     *
     * @param string $txnRef
     * @param int $amount
     * @param array $params
     * @return void
     */
    public function logPaymentInit(string $txnRef, int $amount, array $params = []): void
    {
        $this->log('PAYMENT_INIT', [
            'txn_ref' => $txnRef,
            'amount' => $amount,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'params' => $params,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Log IPN nhận được
     *
     * @param string $ip
     * @param array $data
     * @param bool $isAllowed
     * @return void
     */
    public function logIpnReceived(string $ip, array $data, bool $isAllowed = true): void
    {
        $level = $isAllowed ? 'info' : 'warning';
        $message = $isAllowed ? 'IPN_RECEIVED' : 'IPN_BLOCKED_IP';

        $context = [
            'ip' => $ip,
            'is_allowed' => $isAllowed,
            'txn_ref' => $data['vnp_TxnRef'] ?? null,
            'response_code' => $data['vnp_ResponseCode'] ?? null,
            'transaction_status' => $data['vnp_TransactionStatus'] ?? null,
            'amount' => isset($data['vnp_Amount']) ? (int)$data['vnp_Amount'] / 100 : null,
            'bank_code' => $data['vnp_BankCode'] ?? null,
            'transaction_no' => $data['vnp_TransactionNo'] ?? null,
            'pay_date' => $data['vnp_PayDate'] ?? null,
            'timestamp' => now()->toIso8601String(),
            'raw_data' => $data,
        ];

        if ($level === 'warning') {
            $this->logWarning($message, $context);
        } else {
            $this->log($message, $context);
        }
    }

    /**
     * Log thông tin
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function log(string $message, array $context = []): void
    {
        if (!($this->config['logging']['enabled'] ?? false)) {
            return;
        }

        Log::channel($this->config['logging']['channel'] ?? 'vnpay')->info('[VNPay] ' . $message, $context);
    }

    /**
     * Log warning
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function logWarning(string $message, array $context = []): void
    {
        if (!($this->config['logging']['enabled'] ?? false)) {
            return;
        }

        Log::channel($this->config['logging']['channel'] ?? 'vnpay')->warning('[VNPay] ' . $message, $context);
    }
}