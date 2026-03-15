<?php

namespace VNPayPayment;

/**
 * VNPay Constants
 *
 * Centralized constants for VNPay Payment Gateway integration
 * Eliminates magic strings across all API classes
 */
class VNPayConstants
{
    /**
     * API Commands
     */
    const COMMAND_PAY = 'pay';
    const COMMAND_QUERY = 'querydr';
    const COMMAND_REFUND = 'refund';

    /**
     * VNPay Response Codes - Payment Operations (IPN/Return URL)
     * @see https://sandbox.vnpayment.vn/apis/docs/responses/
     */
    const RESPONSE_SUCCESS = '00';
    const RESPONSE_SUSPICIOUS_TRANSACTION = '07';
    const RESPONSE_NOT_REGISTERED_INTERNET_BANKING = '09';
    const RESPONSE_AUTHENTICATION_FAILED = '10';
    const RESPONSE_PAYMENT_TIMEOUT = '11';
    const RESPONSE_ACCOUNT_LOCKED = '12';
    const RESPONSE_WRONG_PASSWORD_OTP = '13';
    const RESPONSE_CUSTOMER_CANCELLED = '24';
    const RESPONSE_INSUFFICIENT_BALANCE = '51';
    const RESPONSE_EXCEEDED_LIMIT = '65';
    const RESPONSE_BANK_MAINTENANCE = '75';
    const RESPONSE_PASSWORD_WRONG_LIMIT = '79';
    const RESPONSE_UNKNOWN_ERROR = '99';

    /**
     * VNPay Response Codes - Query Transaction Operations
     */
    const RESPONSE_MERCHANT_INVALID = '02';
    const RESPONSE_INVALID_DATA_FORMAT = '03';
    const RESPONSE_TRANSACTION_NOT_FOUND = '91';
    const RESPONSE_DUPLICATE_REQUEST = '94';
    const RESPONSE_INVALID_SIGNATURE = '97';

    /**
     * VNPay Response Codes - Refund Operations
     */
    const RESPONSE_REFUND_AMOUNT_EXCEEDS_ORIGINAL = '02';
    const RESPONSE_REFUND_DATA_INVALID_FORMAT = '03';
    const RESPONSE_FULL_REFUND_NOT_ALLOWED = '04';
    const RESPONSE_PARTIAL_REFUND_ONLY = '13';
    const RESPONSE_REFUND_TRANSACTION_NOT_FOUND = '91';
    const RESPONSE_INVALID_REFUND_AMOUNT = '93';
    const RESPONSE_REFUND_DUPLICATE_REQUEST = '94';
    const RESPONSE_REFUND_FAILED_AT_BANK = '95';
    const RESPONSE_REFUND_INVALID_SIGNATURE = '97';
    const RESPONSE_TIMEOUT_EXCEPTION = '98';

    /**
     * Transaction Status (returned from VNPay)
     * @see https://sandbox.vnpayment.vn/apis/docs/transactions/
     */
    const TRANSACTION_STATUS_SUCCESS = '00';
    const TRANSACTION_STATUS_PENDING = '01';
    const TRANSACTION_STATUS_FAILED = '02';
    const TRANSACTION_STATUS_REVERSED = '04';
    const TRANSACTION_STATUS_REFUNDING = '05';
    const TRANSACTION_STATUS_REFUND_REQUESTED = '06';
    const TRANSACTION_STATUS_FRAUD_SUSPECTED = '07';
    const TRANSACTION_STATUS_TIMEOUT = '08';
    const TRANSACTION_STATUS_REFUND_DENIED = '09';

    /**
     * Transaction Types (for refund operations)
     */
    const TRANSACTION_TYPE_FULL_REFUND = '02';
    const TRANSACTION_TYPE_PARTIAL_REFUND = '03';

    /**
     * Locales
     */
    const LOCALE_VIETNAMESE = 'vn';
    const LOCALE_ENGLISH = 'en';

    /**
     * Order Types
     */
    const ORDER_TYPE_BILLPAYMENT = 'billpayment';
    const ORDER_TYPE_TOPUP = 'topup';
    const ORDER_TYPE_OTHER = 'other';

    /**
     * Currency Codes
     */
    const CURRENCY_VND = 'VND';

    /**
     * Default Values
     */
    const DEFAULT_TRANSACTION_NO = '0';
    const DEFAULT_REQUEST_ID_LENGTH = 32;
    const DEFAULT_QUERY_ORDER_INFO = 'Query transaction';
    const DEFAULT_REFUND_ORDER_INFO = 'Refund transaction';

    /**
     * Field Limits (VNPay API constraints)
     */
    const MAX_ORDER_INFO_LENGTH = 255;
    const MAX_TXN_REF_LENGTH = 100;

    /**
     * Validation Minimums
     */
    const MINIMUM_AMOUNT = 1000000; // 10,000 VND in smallest unit (multiply by 100)

    /**
     * API Version
     */
    const API_VERSION = '2.1.0';

    /**
     * Response Code Messages (Vietnamese)
     * Maps response codes to user-friendly error messages
     */
    public static function getResponseMessages(): array
    {
        return [
            // Payment Response Codes
            '00' => 'Giao dịch thành công',
            '07' => 'Trừ tiền thành công. Giao dịch bị nghi ngờ (liên quan tới lừa đảo, giao dịch bất thường)',
            '09' => 'Giao dịch không thành công do: Thẻ/Tài khoản của khách hàng chưa đăng ký dịch vụ InternetBanking tại ngân hàng',
            '10' => 'Giao dịch không thành công do: Khách hàng xác thực thông tin thẻ/tài khoản không đúng quá 3 lần',
            '11' => 'Giao dịch không thành công do: Đã hết hạn chờ thanh toán. Xin quý khách vui lòng thực hiện lại giao dịch',
            '12' => 'Giao dịch không thành công do: Thẻ/Tài khoản của khách hàng bị khóa',
            '13' => 'Giao dịch không thành công do Quý khách nhập sai mật khẩu xác thực giao dịch (OTP). Xin quý khách vui lòng thực hiện lại giao dịch',
            '24' => 'Giao dịch không thành công do: Khách hàng hủy giao dịch',
            '51' => 'Giao dịch không thành công do: Tài khoản của quý khách không đủ số dư để thực hiện giao dịch',
            '65' => 'Giao dịch không thành công do: Tài khoản của Quý khách đã vượt quá hạn mức giao dịch trong ngày',
            '75' => 'Ngân hàng thanh toán đang bảo trì',
            '79' => 'Giao dịch không thành công do: KH nhập sai mật khẩu thanh toán quá số lần quy định. Xin quý khách vui lòng thực hiện lại giao dịch',
            '99' => 'Các lỗi khác (lỗi còn lại, không có trong danh sách mã lỗi đã liệt kê)',

            // Query & Refund Response Codes
            '02' => 'Merchant không hợp lệ (kiểm tra lại vnp_TmnCode)',
            '03' => 'Dữ liệu gửi sang không đúng định dạng',
            '04' => 'Không cho phép hoàn trả toàn phần sau khi hoàn trả một phần',
            '91' => 'Không tìm thấy giao dịch yêu cầu',
            '93' => 'Số tiền hoàn trả không hợp lệ. Số tiền hoàn trả phải nhỏ hơn hoặc bằng số tiền thanh toán',
            '94' => 'Yêu cầu bị trùng lặp trong thời gian giới hạn của API (Giới hạn trong 5 phút)',
            '95' => 'Giao dịch này không thành công bên VNPAY. VNPAY từ chối xử lý yêu cầu',
            '97' => 'Chữ ký không hợp lệ',
            '98' => 'Timeout Exception',
        ];
    }

    /**
     * Get response message for a given code
     *
     * @param string $code Response code
     * @return string Response message
     */
    public static function getResponseMessage(string $code): string
    {
        $messages = self::getResponseMessages();
        return $messages[$code] ?? 'Lỗi không xác định';
    }
}
