# Laravel VNPay Payment Gateway

[![Latest Version](https://img.shields.io/packagist/v/tuanta1992/laravel-vnpay-payment.svg)](https://packagist.org/packages/tuanta1992/laravel-vnpay-payment)
[![License](https://img.shields.io/packagist/l/tuanta1992/laravel-vnpay-payment.svg)](LICENSE.md)

Package Laravel để tích hợp VNPay Payment Gateway - Cổng thanh toán trực tuyến hàng đầu Việt Nam.

## Tính năng

- ✅ Tạo URL thanh toán VNPay
- ✅ Xác thực Return URL từ VNPay
- ✅ Xử lý IPN (Instant Payment Notification)
- ✅ Truy vấn thông tin giao dịch
- ✅ Hoàn tiền giao dịch (toàn phần/một phần)
- ✅ Hỗ trợ nhiều phương thức thanh toán (ATM, QR, Thẻ quốc tế)
- ✅ Logging chi tiết
- ✅ Command line tools
- ✅ Laravel 10+ & PHP 8.1+

## Yêu cầu

- PHP >= 8.1
- Laravel >= 10.0
- GuzzleHTTP >= 7.0

## Cài đặt

### Cách 1: Từ Packagist (Production)

```bash
composer require tuanta1992/laravel-vnpay-payment
```

### Cách 2: Local Package (Development)

Thêm vào `composer.json` của dự án:

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "packages/laravel-vnpay-payment"
    }
  ],
  "require": {
    "tuanta1992/laravel-vnpay-payment": "@dev"
  }
}
```

Sau đó chạy:

```bash
composer update tuanta1992/laravel-vnpay-payment
```

### Publish Config

```bash
php artisan vendor:publish --tag=vnpay-config
```

### Cấu hình .env

```env
VNPAY_TMN_CODE=your_tmn_code_here
VNPAY_HASH_SECRET=your_hash_secret_here
VNPAY_ENVIRONMENT=sandbox
VNPAY_RETURN_URL="${APP_URL}/vnpay/return"
VNPAY_IPN_URL="${APP_URL}/vnpay/ipn"
VNPAY_LOCALE=vn
VNPAY_EXPIRE_TIME=15
VNPAY_LOG_ENABLED=true
VNPAY_TIMEOUT=30
```

**Lưu ý**: `VNPAY_TMN_CODE` và `VNPAY_HASH_SECRET` được cung cấp bởi VNPay khi đăng ký merchant.

### Kiểm tra cấu hình

```bash
php artisan vnpay:status
```

## Sử dụng

### 1. Tạo URL thanh toán

```php
use VNPayPayment\Facades\VNPay;

$paymentUrl = VNPay::createPaymentUrl([
    'txn_ref' => 'ORDER_' . time(),
    'amount' => 100000, // 100,000 VND
    'order_info' => 'Thanh toan don hang #123',
    'order_type' => 'billpayment',
    'bank_code' => 'VNBANK', // Optional
]);

return redirect($paymentUrl);
```

**Các tham số chính**:
- `txn_ref` (required): Mã tham chiếu giao dịch (unique)
- `amount` (required): Số tiền thanh toán (VND)
- `order_info` (required): Thông tin đơn hàng
- `order_type`: billpayment, topup, other
- `bank_code`: Mã ngân hàng (xem bảng bên dưới)
- `locale`: vn hoặc en
- `ip_addr`, `bill_mobile`, `bill_email`, etc.

### Format tham số - Dual Mode Support

Package này hỗ trợ **HAI cách** truyền tham số:

#### Cách 1: Tham số Abstracted (Đơn giản - Khuyến khích cho người mới)

```php
VNPay::createPaymentUrl([
    'txn_ref' => 'ORDER123',
    'amount' => 100000,  // Auto *100 thành 10000000
    'order_info' => 'Thanh toán đơn hàng',
    'order_type' => 'billpayment',
    'bank_code' => 'VNBANK',
]);
```

**Đặc điểm:**
- Tên tham số đơn giản (snake_case)
- Auto format số tiền (*100)
- Auto xóa dấu tiếng Việt
- Thân thiện với Laravel

#### Cách 2: Tham số trực tiếp VNPay (Full control)

```php
VNPay::createPaymentUrl([
    'vnp_TxnRef' => 'ORDER123',
    'vnp_Amount' => 10000000,  // Pre-formatted (100,000 VND = 10,000,000)
    'vnp_OrderInfo' => 'Thanh toan don hang',
    'vnp_OrderType' => 'billpayment',
    'vnp_BankCode' => 'VNBANK',

    // Optional: Thông tin hóa đơn
    'vnp_Bill_Mobile' => '0912345678',
    'vnp_Bill_Email' => 'user@example.com',
]);
```

**Đặc điểm:**
- Match 1:1 với VNPay document chính thức
- KHÔNG auto format số tiền (bạn chịu trách nhiệm)
- Toàn quyền kiểm soát tham số
- Xóa dấu tiếng Việt vẫn tự động

#### Quy tắc Format Số Tiền

| Format | Tham số | Ví dụ | VNPay Nhận |
|--------|---------|-------|-----------|
| Abstracted | `amount` | 100000 | 10000000 (auto *100) |
| Direct | `vnp_Amount` | 10000000 | 10000000 (no change) |

**⚠️ Quan trọng**: VNPay yêu cầu số tiền ở đơn vị nhỏ nhất (cents):
- 100,000 VND = 10,000,000
- 1,500,000 VND = 150,000,000

### 2. Xử lý Return URL

```php
// routes/web.php
Route::get('/vnpay/return', [PaymentController::class, 'vnpayReturn']);
```

```php
use VNPayPayment\Facades\VNPay;

public function vnpayReturn(Request $request)
{
    $result = VNPay::verifyReturnUrl($request->all());

    if ($result['is_success']) {
        // Thanh toán thành công
        Order::where('txn_ref', $result['txn_ref'])->update([
            'status' => 'paid',
            'vnpay_transaction_no' => $result['transaction_no'],
        ]);

        return view('payment.success', compact('result'));
    }

    return view('payment.failed', compact('result'));
}
```

**Dữ liệu trả về**:
- `is_valid`: Checksum hợp lệ?
- `is_success`: Giao dịch thành công?
- `txn_ref`: Mã tham chiếu
- `amount`: Số tiền (đã chia cho 100)
- `transaction_no`: Mã giao dịch VNPay
- `response_code`: Mã phản hồi (00 = success)
- `message`: Thông báo

### 3. Xử lý IPN (Instant Payment Notification)

```php
// routes/web.php
Route::post('/vnpay/ipn', [PaymentController::class, 'vnpayIpn']);
```

```php
use VNPayPayment\Facades\VNPay;

public function vnpayIpn(Request $request)
{
    $result = VNPay::verifyIpn($request->all());

    if (!$result['is_valid']) {
        return response()->json(['RspCode' => '97', 'Message' => 'Invalid signature']);
    }

    $order = Order::where('txn_ref', $result['txn_ref'])->first();

    if (!$order) {
        return response()->json(['RspCode' => '01', 'Message' => 'Order not found']);
    }

    if ($order->amount != $result['amount']) {
        return response()->json(['RspCode' => '04', 'Message' => 'Invalid amount']);
    }

    if ($order->status == 'paid') {
        return response()->json(['RspCode' => '02', 'Message' => 'Order already confirmed']);
    }

    // Update order status
    if ($result['is_success']) {
        $order->update([
            'status' => 'paid',
            'vnpay_transaction_no' => $result['transaction_no'],
            'paid_at' => now(),
        ]);
    }

    return response()->json(['RspCode' => '00', 'Message' => 'Confirm Success']);
}
```

### 4. Truy vấn giao dịch

```php
$result = VNPay::queryTransaction([
    'txn_ref' => 'ORDER123',
    'transaction_date' => '20231225153000', // YmdHis format
    'order_info' => 'Query transaction',
]);

if ($result['is_success']) {
    $status = $result['transaction_status'];
    $amount = $result['amount'];
}
```

### 5. Hoàn tiền

```php
$result = VNPay::refundTransaction([
    'txn_ref' => 'ORDER123',
    'amount' => 100000,
    'transaction_type' => '02', // 02: toàn phần, 03: một phần
    'transaction_date' => '20231225153000',
    'create_by' => 'admin@example.com',
    'order_info' => 'Refund for order #123',
]);

if ($result['is_success']) {
    $refundTransactionNo = $result['transaction_no'];
}
```

### 6. Helper Methods

```php
// Lấy danh sách bank codes
$bankCodes = VNPay::getBankCodes();

// Lấy tên ngân hàng
$bankName = VNPay::getBankName('VIETCOMBANK');

// Lấy message từ response code
$message = VNPay::getResponseMessage('00');

// Kiểm tra giao dịch thành công
$isSuccess = VNPay::isSuccess('00', '00');
```

## Command Line Tools

```bash
# Test tạo payment URL
php artisan vnpay:test --amount=100000 --txn-ref=TEST123

# Kiểm tra cấu hình
php artisan vnpay:status
```

## Bank Codes

| Mã | Tên |
|---|---|
| VNPAYQR | Ứng dụng hỗ trợ VNPAYQR |
| VNBANK | Thẻ ATM/Tài khoản nội địa |
| INTCARD | Thẻ quốc tế |
| VIETCOMBANK | Vietcombank |
| VIETINBANK | VietinBank |
| BIDV | BIDV |
| AGRIBANK | Agribank |
| SACOMBANK | Sacombank |
| TECHCOMBANK | Techcombank |
| ACB | ACB |
| VPBANK | VPBank |
| TPBANK | TPBank |
| MBBANK | MBBank |
| NCB | NCB |

## Response Codes

| Mã | Ý nghĩa |
|---|---|
| 00 | Giao dịch thành công |
| 05 | Tài khoản không đủ số dư |
| 06 | Sai mật khẩu OTP |
| 07 | Giao dịch nghi ngờ |
| 09 | Chưa đăng ký InternetBanking |
| 10 | Xác thực sai quá 3 lần |
| 11 | Hết hạn chờ thanh toán |
| 12 | Thẻ/Tài khoản bị khóa |
| 24 | Khách hàng hủy giao dịch |
| 65 | Vượt quá hạn mức |
| 75 | Ngân hàng đang bảo trì |
| 79 | Sai mật khẩu quá số lần |
| 99 | Lỗi không xác định |

## Transaction Status

| Mã | Ý nghĩa |
|---|---|
| 00 | Giao dịch thành công |
| 01 | Chưa hoàn tất |
| 02 | Giao dịch bị lỗi |
| 04 | Giao dịch đảo |
| 05 | Đang xử lý hoàn tiền |
| 06 | Đã gửi yêu cầu hoàn tiền |
| 07 | Nghi ngờ gian lận |
| 08 | Quá thời gian thanh toán |
| 09 | Hoàn trả bị từ chối |

## Troubleshooting

### Invalid signature
- Kiểm tra `VNPAY_HASH_SECRET` có đúng không
- Chạy `php artisan config:clear`

### Order not found
- Kiểm tra `txn_ref` có lưu đúng trong DB không
- VNPay có thể gọi IPN nhiều lần

### Connection timeout
- Tăng `VNPAY_TIMEOUT` trong .env
- Kiểm tra kết nối mạng đến VNPay

## Security Best Practices

- Luôn verify checksum từ VNPay
- Không expose `VNPAY_HASH_SECRET`
- Sử dụng HTTPS cho môi trường live
- Validate amount và order info
- Sử dụng rate limiting cho IPN endpoint
- Queue processing cho IPN handlers

## Migration cho GitHub/Packagist

Khi publish package lên GitHub và Packagist:

1. Tạo repository trên GitHub: `tuanta1992/laravel-vnpay-payment`
2. Push code lên GitHub
3. Đăng ký trên [Packagist.org](https://packagist.org/)
4. Link GitHub repository
5. Cập nhật `composer.json` của dự án chính:
   - Xóa `repositories` section
   - Đổi version constraint: `"tuanta1992/laravel-vnpay-payment": "^1.0"`
6. Chạy `composer update`

## Changelog

Xem [CHANGELOG.md](CHANGELOG.md)

## Contributing

Pull requests are welcome!

## License

MIT License. Xem [LICENSE](LICENSE.md)
