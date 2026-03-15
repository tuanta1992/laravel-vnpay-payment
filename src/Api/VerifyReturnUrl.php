<?php

namespace VNPayPayment\Api;

use VNPayPayment\VNPayConstants;
use VNPayPayment\Exceptions\VNPaySignatureException;

class VerifyReturnUrl extends BaseApi
{
    /**
     * Xác thực return URL từ VNPay
     *
     * @param array $params
     * @return array
     */
    public function execute(array $params): array
    {
        // Lấy secure hash từ params
        $vnpSecureHash = $params['vnp_SecureHash'] ?? '';
        unset($params['vnp_SecureHash']);
        unset($params['vnp_SecureHashType']);

        // Tạo hash data
        $hashData = $this->buildHashData($params);

        // Tính secure hash
        $secureHash = $this->createSecureHash($hashData);

        // Verify secure hash
        $isValid = $secureHash === $vnpSecureHash;

        // Throw exception if signature invalid
        if (!$isValid) {
            throw new VNPaySignatureException(
                'Invalid signature from VNPay return URL',
                $secureHash,
                $vnpSecureHash,
                ['txn_ref' => $params['vnp_TxnRef'] ?? '']
            );
        }

        // Parse result
        return [
            'is_valid' => true,
            'txn_ref' => $params['vnp_TxnRef'] ?? '',
            'amount' => isset($params['vnp_Amount']) ? $this->parseAmount($params['vnp_Amount']) : 0,
            'order_info' => $params['vnp_OrderInfo'] ?? '',
            'response_code' => $params['vnp_ResponseCode'] ?? '',
            'transaction_no' => $params['vnp_TransactionNo'] ?? '',
            'bank_code' => $params['vnp_BankCode'] ?? '',
            'bank_tran_no' => $params['vnp_BankTranNo'] ?? '',
            'card_type' => $params['vnp_CardType'] ?? '',
            'pay_date' => isset($params['vnp_PayDate']) ? $this->parseDateTime($params['vnp_PayDate']) : null,
            'transaction_status' => $params['vnp_TransactionStatus'] ?? '',
            'is_success' => ($params['vnp_ResponseCode'] ?? '') === VNPayConstants::RESPONSE_SUCCESS &&
                ($params['vnp_TransactionStatus'] ?? '') === VNPayConstants::TRANSACTION_STATUS_SUCCESS,
            'message' => $this->getResponseMessage($params['vnp_ResponseCode'] ?? ''),
            'raw_data' => $params,
        ];
    }

    /**
     * Get response message
     *
     * @param string $code
     * @return string
     */
    protected function getResponseMessage(string $code): string
    {
        // First check config, then fall back to constants
        return $this->config['response_codes'][$code] ?? VNPayConstants::getResponseMessage($code);
    }
}