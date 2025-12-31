<?php

namespace VNPayPayment\Api;

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
        // Validate required params
        $this->validateRequired($params, ['amount', 'order_info', 'txn_ref']);

        // Build input data
        $inputData = $this->buildInputData($params);

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
     * Build input data
     *
     * @param array $params
     * @return array
     */
    protected function buildInputData(array $params): array
    {
        $createDate = $this->formatDateTime();
        $expireDate = $this->formatDateTime(
            now()->addMinutes($this->config['expire_time'] ?? 15)
        );

        $inputData = [
            'vnp_Version' => $this->config['version'],
            'vnp_Command' => 'pay',
            'vnp_TmnCode' => $this->config['tmn_code'],
            'vnp_Amount' => $this->formatAmount($params['amount']),
            'vnp_CurrCode' => $params['currency'] ?? $this->config['defaults']['currency'],
            'vnp_TxnRef' => $params['txn_ref'],
            'vnp_OrderInfo' => $this->removeAccents($params['order_info']),
            'vnp_OrderType' => $params['order_type'] ?? $this->config['defaults']['order_type'],
            'vnp_Locale' => $params['locale'] ?? $this->config['defaults']['locale'],
            'vnp_ReturnUrl' => $params['return_url'] ?? $this->config['return_url'],
            'vnp_IpAddr' => $params['ip_addr'] ?? $this->getIpAddress(),
            'vnp_CreateDate' => $createDate,
            'vnp_ExpireDate' => $expireDate,
        ];

        // Optional: Bank code
        if (!empty($params['bank_code'])) {
            $inputData['vnp_BankCode'] = $params['bank_code'];
        }

        // Optional: Bill info
        if (!empty($params['bill_mobile'])) {
            $inputData['vnp_Bill_Mobile'] = $params['bill_mobile'];
        }
        if (!empty($params['bill_email'])) {
            $inputData['vnp_Bill_Email'] = $params['bill_email'];
        }
        if (!empty($params['bill_first_name'])) {
            $inputData['vnp_Bill_FirstName'] = $params['bill_first_name'];
        }
        if (!empty($params['bill_last_name'])) {
            $inputData['vnp_Bill_LastName'] = $params['bill_last_name'];
        }
        if (!empty($params['bill_address'])) {
            $inputData['vnp_Bill_Address'] = $params['bill_address'];
        }
        if (!empty($params['bill_city'])) {
            $inputData['vnp_Bill_City'] = $params['bill_city'];
        }
        if (!empty($params['bill_country'])) {
            $inputData['vnp_Bill_Country'] = $params['bill_country'];
        }

        // Optional: Invoice info
        if (!empty($params['inv_phone'])) {
            $inputData['vnp_Inv_Phone'] = $params['inv_phone'];
        }
        if (!empty($params['inv_email'])) {
            $inputData['vnp_Inv_Email'] = $params['inv_email'];
        }
        if (!empty($params['inv_customer'])) {
            $inputData['vnp_Inv_Customer'] = $params['inv_customer'];
        }
        if (!empty($params['inv_address'])) {
            $inputData['vnp_Inv_Address'] = $params['inv_address'];
        }
        if (!empty($params['inv_company'])) {
            $inputData['vnp_Inv_Company'] = $params['inv_company'];
        }
        if (!empty($params['inv_taxcode'])) {
            $inputData['vnp_Inv_Taxcode'] = $params['inv_taxcode'];
        }
        if (!empty($params['inv_type'])) {
            $inputData['vnp_Inv_Type'] = $params['inv_type'];
        }

        return $inputData;
    }

    /**
     * Remove Vietnamese accents
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
}