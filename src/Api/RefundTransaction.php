<?php

namespace VNPayPayment\Api;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class RefundTransaction extends BaseApi
{
    /**
     * Hoàn tiền giao dịch
     *
     * @param array $params
     * @return array
     * @throws \Exception
     */
    public function execute(array $params): array
    {
        // Validate required params
        $this->validateRequired($params, [
            'txn_ref',
            'amount',
            'transaction_type',
            'transaction_date',
            'create_by'
        ]);

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
            throw new \Exception('Refund transaction failed: ' . $e->getMessage());
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
        $requestId = $params['request_id'] ?? $this->generateTxnRef(32);
        $createDate = $this->formatDateTime();

        $data = [
            'vnp_RequestId' => $requestId,
            'vnp_Version' => $this->config['version'],
            'vnp_Command' => 'refund',
            'vnp_TmnCode' => $this->config['tmn_code'],
            'vnp_TransactionType' => $params['transaction_type'], // 02: full, 03: partial
            'vnp_TxnRef' => $params['txn_ref'],
            'vnp_Amount' => $this->formatAmount($params['amount']),
            'vnp_OrderInfo' => $params['order_info'] ?? 'Refund transaction',
            'vnp_TransactionDate' => $params['transaction_date'],
            'vnp_CreateBy' => $params['create_by'],
            'vnp_CreateDate' => $createDate,
            'vnp_IpAddr' => $params['ip_addr'] ?? $this->getIpAddress(),
        ];

        // Optional: transaction_no
        if (!empty($params['transaction_no'])) {
            $data['vnp_TransactionNo'] = $params['transaction_no'];
        } else {
            $data['vnp_TransactionNo'] = '0';
        }

        // Create secure hash
        $hashData = sprintf(
            '%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s',
            $data['vnp_RequestId'],
            $data['vnp_Version'],
            $data['vnp_Command'],
            $data['vnp_TmnCode'],
            $data['vnp_TransactionType'],
            $data['vnp_TxnRef'],
            $data['vnp_Amount'],
            $data['vnp_TransactionNo'],
            $data['vnp_TransactionDate'],
            $data['vnp_CreateBy'],
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
            'pay_date' => isset($result['vnp_PayDate']) ? $this->parseDateTime($result['vnp_PayDate']) : null,
            'transaction_no' => $result['vnp_TransactionNo'] ?? '',
            'transaction_type' => $result['vnp_TransactionType'] ?? '',
            'transaction_status' => $result['vnp_TransactionStatus'] ?? '',
            'is_success' => ($result['vnp_ResponseCode'] ?? '') === '00',
            'raw_data' => $result,
        ];
    }
}