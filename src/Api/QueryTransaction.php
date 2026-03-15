<?php

namespace VNPayPayment\Api;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use VNPayPayment\VNPayConstants;
use VNPayPayment\Exceptions\VNPayValidationException;
use VNPayPayment\Exceptions\VNPayRequestException;

class QueryTransaction extends BaseApi
{
    /**
     * Truy vấn thông tin giao dịch
     *
     * @param array $params
     * @return array
     * @throws VNPayValidationException
     * @throws VNPayRequestException
     */
    public function execute(array $params): array
    {
        // Validate required params
        $this->validateRequired($params, ['txn_ref', 'transaction_date']);

        // Build request data
        $requestData = $this->buildRequestData($params);

        // Send request
        try {
            $client = new Client([
                'timeout' => $this->config['timeout'] ?? 30,
            ]);

            $response = $client->post($this->getUrl('api'), [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => $requestData,
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            return $this->parseResponse($result);

        } catch (GuzzleException $e) {
            throw new VNPayRequestException(
                'Query transaction request failed: ' . $e->getMessage(),
                url: $this->getUrl('api'),
                method: 'POST',
                attempts: 1,
                context: ['txn_ref' => $requestData['vnp_TxnRef'] ?? ''],
                previous: $e
            );
        }
    }

    /**
     * Build request data
     *
     * @param array $params
     * @return array
     */
    protected function buildRequestData(array $params): array
    {
        // Normalize dual-mode parameters: vnp_ prefix takes priority
        $normalized = $this->normalizeParams($params, [
            'vnp_TxnRef' => 'txn_ref',
            'vnp_TransactionDate' => 'transaction_date',
            'vnp_OrderInfo' => 'order_info',
            'vnp_TransactionNo' => 'transaction_no',
            'vnp_RequestId' => 'request_id',
            'vnp_IpAddr' => 'ip_addr',
        ]);

        // Extract with defaults
        $txnRef = $normalized['vnp_TxnRef'] ?? null;
        $transactionDate = $normalized['vnp_TransactionDate'] ?? null;
        $orderInfo = $normalized['vnp_OrderInfo'] ?? VNPayConstants::DEFAULT_QUERY_ORDER_INFO;
        $transactionNo = $normalized['vnp_TransactionNo'] ?? null;
        $requestId = $normalized['vnp_RequestId'] ?? $this->generateTxnRef(VNPayConstants::DEFAULT_REQUEST_ID_LENGTH);
        $ipAddr = $normalized['vnp_IpAddr'] ?? $this->getIpAddress();

        if (!$txnRef || !$transactionDate) {
            throw new VNPayValidationException(
                'txn_ref|transaction_date',
                'Missing required parameters: txn_ref/vnp_TxnRef and transaction_date/vnp_TransactionDate'
            );
        }

        $createDate = $this->formatDateTime();

        $data = [
            'vnp_RequestId' => $requestId,
            'vnp_Version' => $this->config['version'],
            'vnp_Command' => VNPayConstants::COMMAND_QUERY,
            'vnp_TmnCode' => $this->config['tmn_code'],
            'vnp_TxnRef' => $txnRef,
            'vnp_OrderInfo' => $this->removeAccents($orderInfo),
            'vnp_TransactionDate' => $transactionDate,
            'vnp_CreateDate' => $createDate,
            'vnp_IpAddr' => $ipAddr,
        ];

        // Optional: transaction_no
        if (!empty($transactionNo)) {
            $data['vnp_TransactionNo'] = $transactionNo;
        }

        // Create secure hash
        $hashData = sprintf(
            '%s|%s|%s|%s|%s|%s|%s|%s|%s',
            $data['vnp_RequestId'],
            $data['vnp_Version'],
            $data['vnp_Command'],
            $data['vnp_TmnCode'],
            $data['vnp_TxnRef'],
            $data['vnp_TransactionDate'],
            $data['vnp_CreateDate'],
            $data['vnp_IpAddr'],
            $data['vnp_OrderInfo']
        );

        $data['vnp_SecureHash'] = $this->createSecureHash($hashData);

        return $data;
    }


    /**
     * Parse response
     *
     * @param array $result
     * @return array
     */
    protected function parseResponse(array $result): array
    {
        return [
            'response_id' => $result['vnp_ResponseId'] ?? '',
            'command' => $result['vnp_Command'] ?? '',
            'response_code' => $result['vnp_ResponseCode'] ?? '',
            'message' => $result['vnp_Message'] ?? '',
            'tmn_code' => $result['vnp_TmnCode'] ?? '',
            'txn_ref' => $result['vnp_TxnRef'] ?? '',
            'amount' => isset($result['vnp_Amount']) ? $this->parseAmount($result['vnp_Amount']) : 0,
            'order_info' => $result['vnp_OrderInfo'] ?? '',
            'bank_code' => $result['vnp_BankCode'] ?? '',
            'card_type' => $result['vnp_CardType'] ?? '',
            'pay_date' => isset($result['vnp_PayDate']) ? $this->parseDateTime($result['vnp_PayDate']) : null,
            'transaction_no' => $result['vnp_TransactionNo'] ?? '',
            'transaction_type' => $result['vnp_TransactionType'] ?? '',
            'transaction_status' => $result['vnp_TransactionStatus'] ?? '',
            'promotion_code' => $result['vnp_PromotionCode'] ?? '',
            'promotion_amount' => isset($result['vnp_PromotionAmount']) ? $this->parseAmount($result['vnp_PromotionAmount']) : 0,
            'is_success' => ($result['vnp_ResponseCode'] ?? '') === VNPayConstants::RESPONSE_SUCCESS,
            'raw_data' => $result,
        ];
    }
}