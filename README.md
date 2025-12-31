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

### Bước 1: Cài đặt package qua Composer

```bash
composer require tuanta1992/laravel-vnpay-payment
```

### Bước 2: Publish config file

```bash
php artisan vendor:publish --tag=vnpay-config
```

### Bước 3: Cấu hình .env

Thêm các biến sau vào file `.env`:

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

**Lưu ý:**
- Môi trường `VNPAY_ENVIRONMENT` có thể là `sandbox` hoặc `production`
- `VNPAY_TMN_CODE` và `VNPAY_HASH_SECRET` được cung cấp bởi VNPay khi đăng ký

### Bước 4: Kiểm tra cấu hình

```bash
php artisan vnpay:status
```

Output mẫu:
```
VNPay Configuration Status
========================================
TMN Code             VNPAY001...                    ✓
Hash Secret          ***                            ✓
Environment          sandbox                        ✓
Payment URL          https://sandbox...             ✓
API URL              https://sandbox...             ✓
Return URL           http://localhost/vnpay/return  ✓
IPN URL              http://localhost/vnpay/ipn     ✓
Logging              Enabled                        ✓
========================================
✓ Configuration looks good!
```

## Sử dụng

### 1. Tạo URL thanh toán

#### Sử dụng Facade

```php
use VNPayPayment\Facades\VNPay;

// Tạo URL thanh toán
$paymentUrl = VNPay::createPaymentUrl([
    'txn_ref' => 'ORDER_' . time(),
    'amount' => 100000, // 100,000 VND
    'order_info' => 'Thanh toan don hang #123',
    'order_type' => 'billpayment', // billpayment, topup, other
    'bank_code' => 'VNBANK', // Optional: Chọn bank code cụ thể
    'locale' => 'vn', // vn hoặc en
]);

// Redirect đến URL thanh toán
return redirect($paymentUrl);
```

#### Sử dụng Dependency Injection

```php
use VNPayPayment\VNPayClient;

public function createPayment(VNPayClient $vnpay)
{
    $paymentUrl = $vnpay->createPaymentUrl([
        'txn_ref' => 'ORDER_' . time(),
        'amount' => 100000,
        'order_info' => 'Thanh toan don hang #123',
    ]);

    return redirect($paymentUrl);
}
```

#### Các tham số tùy chọn

```php
$paymentUrl = VNPay::createPaymentUrl([
    // Required
    'txn_ref' => 'ORDER123',
    'amount' => 100000,
    'order_info' => 'Payment for order #123',
    
    // Optional
    'bank_code' => 'VNBANK',
    'locale' => 'vn',
    'currency' => 'VND',
    'order_type' => 'billpayment',
    'return_url' => route('custom.return'),
    'ip_addr' => request()->ip(),
    
    // Bill info
    'bill_mobile' => '0912345678',
    'bill_email' => 'customer@example.com',
    'bill_first_name' => 'Nguyen',
    'bill_last_name' => 'Van A',
    'bill_address' => '22 Lang Ha',
    'bill_city' => 'Ha Noi',
    'bill_country' => 'VN',
    
    // Invoice info
    'inv_phone' => '0912345678',
    'inv_email' => 'invoice@company.com',
    'inv_customer' => 'Nguyen Van A',
    'inv_address' => '22 Lang Ha, Dong Da',
    'inv_company' => 'ABC Company',
    'inv_taxcode' => '0123456789',
    'inv_type' => 'I', // I: Individual, O: Organization
]);
```

### 2. Xử lý Return URL (Khách hàng quay lại)

Tạo route và controller để xử lý khi khách hàng thanh toán xong:

```php
// routes/web.php
Route::get('/vnpay/return', [PaymentController::class, 'vnpayReturn'])->name('vnpay.return');
```

```php
// app/Http/Controllers/PaymentController.php
use VNPayPayment\Facades\VNPay;

public function vnpayReturn(Request $request)
{
    $result = VNPay::verifyReturnUrl($request->all());
    
    if ($result['is_success']) {
        // Thanh toán thành công
        $txnRef = $result['txn_ref'];
        $amount = $result['amount'];
        $transactionNo = $result['transaction_no'];
        
        // Update order status
        Order::where('txn_ref', $txnRef)->update([
            'status' => 'paid',
            'vnpay_transaction_no' => $transactionNo,
        ]);
        
        return view('payment.success', compact('result'));
    } else {
        // Thanh toán thất bại
        $message = $result['message'];
        
        return view('payment.failed', compact('result'));
    }
}
```

Thông tin trả về từ `verifyReturnUrl()`:

```php
[
    'is_valid' => true,              // Checksum hợp lệ?
    'is_success' => true,            // Giao dịch thành công?
    'txn_ref' => 'ORDER123',        
    'amount' => 100000,              // Đã chia cho 100
    'order_info' => 'Payment...',
    'response_code' => '00',         // 00 = Success
    'transaction_no' => '14012345',  // Mã GD VNPay
    'bank_code' => 'VIETCOMBANK',
    'card_type' => 'ATM',
    'pay_date' => DateTime,
    'transaction_status' => '00',
    'message' => 'Giao dịch thành công',
    'raw_data' => [...],             // Full data từ VNPay
]
```

### 3. Xử lý IPN (Instant Payment Notification)

VNPay sẽ gọi IPN URL để thông báo kết quả giao dịch. Đây là cơ chế đảm bảo merchant nhận được thông báo ngay cả khi khách hàng đóng trình duyệt.

```php
// routes/web.php
Route::post('/vnpay/ipn', [PaymentController::class, 'vnpayIpn'])->name('vnpay.ipn');
```

```php
use VNPayPayment\Facades\VNPay;
use VNPayPayment\Api\VerifyIpn;

public function vnpayIpn(Request $request)
{
    $result = VNPay::verifyIpn($request->all());
    
    if (!$result['is_valid']) {
        return response()->json([
            'RspCode' => '97',
            'Message' => 'Invalid signature'
        ]);
    }
    
    // Find order
    $order = Order::where('txn_ref', $result['txn_ref'])->first();
    
    if (!$order) {
        return response()->json([
            'RspCode' => '01',
            'Message' => 'Order not found'
        ]);
    }
    
    // Check amount
    if ($order->amount != $result['amount']) {
        return response()->json([
            'RspCode' => '04',
            'Message' => 'Invalid amount'
        ]);
    }
    
    // Check if already updated
    if ($order->status == 'paid') {
        return response()->json([
            'RspCode' => '02',
            'Message' => 'Order already confirmed'
        ]);
    }
    
    // Update order
    if ($result['is_success']) {
        $order->update([
            'status' => 'paid',
            'vnpay_transaction_no' => $result['transaction_no'],
            'paid_at' => now(),
        ]);
    } else {
        $order->update([
            'status' => 'failed',
            'vnpay_response_code' => $result['response_code'],
        ]);
    }
    
    return response()->json([
        'RspCode' => '00',
        'Message' => 'Confirm Success'
    ]);
}
```

### 4. Truy vấn thông tin giao dịch

```php
use VNPayPayment\Facades\VNPay;

$result = VNPay::queryTransaction([
    'txn_ref' => 'ORDER123',
    'transaction_date' => '20231225153000', // YmdHis format
    'order_info' => 'Query transaction',
]);

if ($result['is_success']) {
    $status = $result['transaction_status'];
    $amount = $result['amount'];
    $transactionNo = $result['transaction_no'];
}
```

### 5. Hoàn tiền

```php
use VNPayPayment\Facades\VNPay;

$result = VNPay::refundTransaction([
    'txn_ref' => 'ORDER123',
    'amount' => 100000,
    'transaction_type' => '02', // 02: toàn phần, 03: một phần
    'transaction_date' => '20231225153000',
    'create_by' => 'admin@example.com',
    'order_info' => 'Refund for order #123',
]);

if ($result['is_success']) {
    // Hoàn tiền thành công
    $refundTransactionNo = $result['transaction_no'];
}
```

### 6. Các helper methods

```php
use VNPayPayment\Facades\VNPay;

// Lấy danh sách bank codes
$bankCodes = VNPay::getBankCodes();
// => ['VNBANK' => 'ATM/Tài khoản nội địa', ...]

// Lấy tên ngân hàng
$bankName = VNPay::getBankName('VIETCOMBANK');
// => 'Ngân hàng TMCP Ngoại thương Việt Nam'

// Lấy message từ response code
$message = VNPay::getResponseMessage('00');
// => 'Giao dịch thành công'

// Lấy message từ transaction status
$statusMessage = VNPay::getTransactionStatusMessage('00');
// => 'Giao dịch thành công'

// Kiểm tra giao dịch thành công
$isSuccess = VNPay::isSuccess('00', '00');
// => true
```

## Command Line Tools

### Test tạo payment URL

```bash
php artisan vnpay:test --amount=100000 --txn-ref=TEST123
```

### Kiểm tra cấu hình

```bash
php artisan vnpay:status
```

## Mã ngân hàng (Bank Codes)

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

## Testing

```bash
composer test
```

## Best Practices

### 1. Sử dụng Queue cho IPN

```php
use Illuminate\Bus\Queueable;

class ProcessVNPayIPN implements ShouldQueue
{
    use Queueable;
    
    public function __construct(public array $ipnData) {}
    
    public function handle()
    {
        // Process IPN
    }
}
```

### 2. Rate Limiting

```php
Route::post('/vnpay/ipn', [...])
    ->middleware('throttle:10,1'); // 10 requests per minute
```

### 3. Database Migration Example

```php
Schema::create('orders', function (Blueprint $table) {
    $table->id();
    $table->string('txn_ref')->unique();
    $table->decimal('amount', 15, 2);
    $table->enum('status', ['pending', 'paid', 'failed', 'refunded']);
    $table->string('vnpay_transaction_no')->nullable();
    $table->string('vnpay_response_code')->nullable();
    $table->timestamp('paid_at')->nullable();
    $table->timestamps();
});
```

### 4. Logging

Package tự động log tất cả các API calls nếu bật `VNPAY_LOG_ENABLED=true`:

```
[VNPay] Creating payment URL: {...}
[VNPay] Payment URL created: {...}
[VNPay] Verifying return URL: {...}
[VNPay] Return URL verified: {...}
```

## Troubleshooting

### Lỗi: Invalid signature

- Kiểm tra `VNPAY_HASH_SECRET` có đúng không
- Đảm bảo không thay đổi params từ VNPay

### Lỗi: Order not found

- Kiểm tra `txn_ref` có lưu đúng trong DB không
- VNPay có thể gọi IPN nhiều lần

### Lỗi: Timeout

- Tăng `VNPAY_TIMEOUT` trong .env
- Kiểm tra kết nối mạng đến VNPay

## Security

- Luôn verify checksum từ VNPay
- Không expose `VNPAY_HASH_SECRET`
- Sử dụng HTTPS cho production
- Validate amount và order info

## Changelog

Xem [CHANGELOG.md](CHANGELOG.md)

## Contributing

Pull requests are welcome!

## License

MIT License. Xem [LICENSE](LICENSE.md)