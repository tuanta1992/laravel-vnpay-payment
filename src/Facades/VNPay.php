<?php

namespace VNPayPayment\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string createPaymentUrl(array $params)
 * @method static array verifyReturnUrl(array $params)
 * @method static array verifyIpn(array $params)
 * @method static array queryTransaction(array $params)
 * @method static array refundTransaction(array $params)
 * @method static array getBankCodes()
 * @method static string|null getBankName(string $code)
 * @method static string|null getResponseMessage(string $code)
 * @method static string|null getTransactionStatusMessage(string $status)
 * @method static bool isSuccess(string $responseCode, string|null $transactionStatus = null)
 *
 * @see \VNPayPayment\VNPayClient
 */
class VNPay extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'vnpay';
    }
}