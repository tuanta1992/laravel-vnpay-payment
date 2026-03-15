<?php

namespace VNPayPayment\Api;

use VNPayPayment\VNPayConstants;
use VNPayPayment\Exceptions\VNPaySignatureException;

class VerifyIpn extends BaseApi
{
    /**
     * Xác thực IPN từ VNPay
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

        // Nếu checksum không hợp lệ
        if (!$isValid) {
            throw new VNPaySignatureException(
                'Invalid signature from VNPay IPN',
                $secureHash,
                $vnpSecureHash,
                ['txn_ref' => $params['vnp_TxnRef'] ?? '']
            );
        }

        // Parse transaction info
        $txnRef = $params['vnp_TxnRef'] ?? '';
        $amount = isset($params['vnp_Amount']) ? $this->parseAmount($params['vnp_Amount']) : 0;
        $responseCode = $params['vnp_ResponseCode'] ?? '';
        $transactionStatus = $params['vnp_TransactionStatus'] ?? '';
        $transactionNo = $params['vnp_TransactionNo'] ?? '';
        $bankCode = $params['vnp_BankCode'] ?? '';

        // Return parsed data
        // Merchant cần kiểm tra:
        // 1. Order tồn tại trong DB
        // 2. Số tiền khớp
        // 3. Trạng thái order chưa được cập nhật
        // Sau đó trả về RspCode tương ứng

        return [
            'is_valid' => true,
            'txn_ref' => $txnRef,
            'amount' => $amount,
            'response_code' => $responseCode,
            'transaction_status' => $transactionStatus,
            'transaction_no' => $transactionNo,
            'bank_code' => $bankCode,
            'is_success' => $responseCode === VNPayConstants::RESPONSE_SUCCESS && $transactionStatus === VNPayConstants::TRANSACTION_STATUS_SUCCESS,
            'raw_data' => $params,
        ];
    }

    /**
     * Create IPN response
     *
     * @param string $rspCode
     * @param string $message
     * @return array
     */
    public static function createResponse(string $rspCode, string $message): array
    {
        return [
            'RspCode' => $rspCode,
            'Message' => $message,
        ];
    }
}