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
        $url = $api->execute($params);

        $this->log('Payment URL created', ['url' => $url]);

        return $url;
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
        $this->log('Verifying IPN', $params);

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
        return $this->config['response_codes'][$code] ?? null;
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

        Log::channel($this->config['logging']['channel'] ?? 'stack')->info('[VNPay] ' . $message, $context);
    }
}