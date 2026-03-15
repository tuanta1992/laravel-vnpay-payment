<?php

namespace VNPayPayment\Api;

use VNPayPayment\VNPayConstants;

class CreatePayment extends BaseApi
{
    /**
     * Tạo URL thanh toán
     *
     * @param array $params
     * @return string
     */
    public function execute(array $params): string
    {
        // Detect mode: direct VNPay parameters or abstracted keys
        $isDirect = isset($params['vnp_TxnRef']) || isset($params['vnp_Amount']) || isset($params['vnp_OrderInfo']);

        if ($isDirect) {
            // Validate direct VNPay parameters
            $this->validateVnpParams($params, ['vnp_TxnRef', 'vnp_Amount', 'vnp_OrderInfo']);
            $this->validateVnpAmount($params['vnp_Amount']);
        } else {
            // Validate abstracted parameters
            $this->validateRequired($params, ['amount', 'order_info', 'txn_ref']);
        }

        // Build input data
        $inputData = $this->buildInputData($params, $isDirect);

        // Create query string
        $query = $this->buildQueryString($inputData);

        // Create hash data
        $hashData = $this->buildHashData($inputData);

        // Create secure hash
        $secureHash = $this->createSecureHash($hashData);

        // Build final URL
        $paymentUrl = $this->getUrl('payment') . '?' . $query . '&vnp_SecureHash=' . $secureHash;

        return $paymentUrl;
    }

    /**
     * Build input data - dispatcher method
     *
     * @param array $params
     * @param bool $isDirect
     * @return array
     */
    protected function buildInputData(array $params, bool $isDirect = false): array
    {
        if ($isDirect) {
            return $this->buildDirectInputData($params);
        } else {
            return $this->buildAbstractedInputData($params);
        }
    }

    /**
     * Build input data - direct VNPay parameters mode
     *
     * @param array $params
     * @return array
     */
    protected function buildDirectInputData(array $params): array
    {
        $createDate = $this->formatDateTime();
        $expireDate = $this->formatDateTime(
            now()->addMinutes($this->config['expire_time'] ?? 15)
        );

        // Build base input data with direct VNPay parameters (NO formatting on amount)
        $inputData = [
            'vnp_Version' => $this->config['version'],
            'vnp_Command' => VNPayConstants::COMMAND_PAY,
            'vnp_TmnCode' => $this->config['tmn_code'],
            'vnp_Amount' => $params['vnp_Amount'], // NO *100 formatting
            'vnp_CurrCode' => $params['vnp_CurrCode'] ?? $this->config['defaults']['currency'] ?? VNPayConstants::CURRENCY_VND,
            'vnp_TxnRef' => $params['vnp_TxnRef'],
            'vnp_OrderInfo' => $this->truncateOrderInfo($this->removeAccents($params['vnp_OrderInfo'])), // Remove accents + truncate to 255 chars
            'vnp_OrderType' => $params['vnp_OrderType'] ?? $this->config['defaults']['order_type'] ?? VNPayConstants::ORDER_TYPE_OTHER,
            'vnp_Locale' => $params['vnp_Locale'] ?? $this->config['defaults']['locale'] ?? VNPayConstants::LOCALE_VIETNAMESE,
            'vnp_ReturnUrl' => $params['vnp_ReturnUrl'] ?? $this->config['return_url'],
            'vnp_IpAddr' => $params['vnp_IpAddr'] ?? $this->getIpAddress(),
            'vnp_CreateDate' => $createDate,
            'vnp_ExpireDate' => $expireDate,
        ];

        // Optional fields: billing, invoice info, bank code
        $optionalFields = [
            'vnp_BankCode',
            'vnp_Bill_Mobile', 'vnp_Bill_Email', 'vnp_Bill_FirstName',
            'vnp_Bill_LastName', 'vnp_Bill_Address', 'vnp_Bill_City',
            'vnp_Bill_Country', 'vnp_Bill_State',
            'vnp_Inv_Phone', 'vnp_Inv_Email', 'vnp_Inv_Customer',
            'vnp_Inv_Address', 'vnp_Inv_Company', 'vnp_Inv_Taxcode', 'vnp_Inv_Type'
        ];

        return $this->applyOptionalFields($inputData, $params, $optionalFields);
    }

    /**
     * Build input data - abstracted parameters mode (legacy)
     *
     * @param array $params
     * @return array
     */
    protected function buildAbstractedInputData(array $params): array
    {
        $createDate = $this->formatDateTime();
        $expireDate = $this->formatDateTime(
            now()->addMinutes($this->config['expire_time'] ?? 15)
        );

        $inputData = [
            'vnp_Version' => $this->config['version'],
            'vnp_Command' => VNPayConstants::COMMAND_PAY,
            'vnp_TmnCode' => $this->config['tmn_code'],
            'vnp_Amount' => $this->formatAmount($params['amount']), // Apply *100
            'vnp_CurrCode' => $params['currency'] ?? $this->config['defaults']['currency'] ?? VNPayConstants::CURRENCY_VND,
            'vnp_TxnRef' => $params['txn_ref'],
            'vnp_OrderInfo' => $this->truncateOrderInfo($this->removeAccents($params['order_info'])), // Remove accents + truncate to 255 chars
            'vnp_OrderType' => $params['order_type'] ?? $this->config['defaults']['order_type'] ?? VNPayConstants::ORDER_TYPE_OTHER,
            'vnp_Locale' => $params['locale'] ?? $this->config['defaults']['locale'] ?? VNPayConstants::LOCALE_VIETNAMESE,
            'vnp_ReturnUrl' => $params['return_url'] ?? $this->config['return_url'],
            'vnp_IpAddr' => $params['ip_addr'] ?? $this->getIpAddress(),
            'vnp_CreateDate' => $createDate,
            'vnp_ExpireDate' => $expireDate,
        ];

        // Map abstracted optional fields to VNPay format and apply them
        $abstractedOptionalFields = [
            'bank_code' => 'vnp_BankCode',
            'bill_mobile' => 'vnp_Bill_Mobile',
            'bill_email' => 'vnp_Bill_Email',
            'bill_first_name' => 'vnp_Bill_FirstName',
            'bill_last_name' => 'vnp_Bill_LastName',
            'bill_address' => 'vnp_Bill_Address',
            'bill_city' => 'vnp_Bill_City',
            'bill_country' => 'vnp_Bill_Country',
            'inv_phone' => 'vnp_Inv_Phone',
            'inv_email' => 'vnp_Inv_Email',
            'inv_customer' => 'vnp_Inv_Customer',
            'inv_address' => 'vnp_Inv_Address',
            'inv_company' => 'vnp_Inv_Company',
            'inv_taxcode' => 'vnp_Inv_Taxcode',
            'inv_type' => 'vnp_Inv_Type'
        ];

        foreach ($abstractedOptionalFields as $abstractedKey => $vnpKey) {
            if (!empty($params[$abstractedKey])) {
                $inputData[$vnpKey] = $params[$abstractedKey];
            }
        }

        return $inputData;
    }

}