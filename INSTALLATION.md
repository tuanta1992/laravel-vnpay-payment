# Hướng dẫn cài đặt chi tiết Laravel VNPay Payment

## Mục lục
1. [Yêu cầu hệ thống](#yêu-cầu-hệ-thống)
2. [Đăng ký VNPay Merchant](#đăng-ký-vnpay-merchant)
3. [Cài đặt Package](#cài-đặt-package)
4. [Cấu hình](#cấu-hình)
5. [Triển khai Routes và Controllers](#triển-khai-routes-và-controllers)
6. [Testing](#testing)
7. [Production Deployment](#production-deployment)

## Yêu cầu hệ thống

- PHP >= 8.1
- Laravel >= 10.0
- Composer
- GuzzleHTTP >= 7.0
- Extension: curl, json, mbstring

## Đăng ký VNPay Merchant

### Bước 1: Đăng ký tài khoản

1. Truy cập [VNPay Merchant Portal](https://vnpay.vn)
2. Đăng ký tài khoản merchant
3. Hoàn tất thủ tục KYC và hợp đồng

### Bước 2: Lấy thông tin cấu hình

Sau khi đăng ký thành công, bạn sẽ nhận được:

- **TMN Code** (Terminal Code): Mã định danh merchant
- **Hash Secret**: Secret key để tạo checksum
- **Merchant ID**: ID merchant

**Lưu ý:** Thông tin này rất quan trọng và cần được bảo mật tuyệt đối.

### Bước 3: Cấu hình môi trường test

VNPay cung cấp môi trường sandbox để test:
- URL: https://sandbox.vnpayment.vn
- Thông tin test card: Xem tại merchant portal

## Cài đặt Package

### Cách 1: Cài từ Packagist (Khuyến nghị)

```bash
composer require anhtuan92na/laravel-vnpay-payment
```

### Cách 2: Cài từ GitHub

Thêm vào `composer.json`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/anhtuan92na/laravel-vnpay-payment"
        }
    ],
    "require": {
        "anhtuan92na/laravel-vnpay-payment": "^1.0"
    }
}
```

Sau đó:

```bash
composer update
```

### Cách 3: Cài từ Local (Development)

Nếu bạn đang phát triển package:

```bash
# Clone package vào thư mục packages
mkdir packages
cd packages
git clone https://github.com/anhtuan92na/laravel-vnpay-payment.git

# Quay lại thư mục root của Laravel project
cd ..
```

Thêm vào `composer.json`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "packages/laravel-vnpay-payment"
        }
    ],
    "require": {
        "anhtuan92na/laravel-vnpay-payment": "@dev"
    }
}
```

Cài đặt:

```bash
composer update anhtuan92na/laravel-vnpay-payment
```

## Cấu hình

### Bước 1: Publish config file

```bash
php artisan vendor:publish --tag=vnpay-config
```

File config sẽ được tạo tại `config/vnpay.php`

### Bước 2: Cấu hình .env

Copy file `.env.example` và cập nhật:

```env
VNPAY_TMN_CODE=VNPAY001
VNPAY_HASH_SECRET=your_secret_key_here
VNPAY_ENVIRONMENT=sandbox
VNPAY_RETURN_URL="${APP_URL}/vnpay/return"
VNPAY_IPN_URL="${APP_URL}/vnpay/ipn"
VNPAY_LOCALE=vn
VNPAY_EXPIRE_TIME=15
VNPAY_LOG_ENABLED=true
VNPAY_LOG_CHANNEL=stack
VNPAY_TIMEOUT=30
```

### Bước 3: Clear cache

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

### Bước 4: Kiểm tra cấu hình

```bash
php artisan vnpay:status
```

Kết quả mong đợi:

```
VNPay Configuration Status
========================================
TMN Code             VNPAY001                       ✓
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

## Triển khai Routes và Controllers

### Bước 1: Tạo Controller

```bash
php artisan make:controller PaymentController
```

### Bước 2: Implement Payment Methods

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use AnhTuan92Na\VNPayPayment\Facades\VNPay;
use App\Models\Order;

class PaymentController extends Controller
{
    /**
     * Tạo payment URL và redirect
     */
    public function createPayment(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
        ]);

        $order = Order::findOrFail($request->order_id);

        // Tạo transaction reference
        $txnRef = 'ORDER_' . $order->id . '_' . time();

        // Lưu txn_ref vào order
        $order->update(['txn_ref' => $txnRef]);

        // Tạo payment URL
        $paymentUrl = VNPay::createPaymentUrl([
            'txn_ref' => $txnRef,
            'amount' => $order->total_amount,
            'order_info' => 'Thanh toan don hang #' . $order->id,
            'order_type' => 'billpayment',
            'bank_code' => $request->bank_code, // Optional
        ]);

        return redirect($paymentUrl);
    }

    /**
     * Xử lý return URL
     */
    public function vnpayReturn(Request $request)
    {
        $result = VNPay::verifyReturnUrl($request->all());

        if ($result['is_success']) {
            // Tìm order
            $order = Order::where('txn_ref', $result['txn_ref'])->first();

            if ($order) {
                $order->update([
                    'status' => 'paid',
                    'vnpay_transaction_no' => $result['transaction_no'],
                    'paid_at' => now(),
                ]);

                return redirect()->route('order.success', $order->id)
                    ->with('success', 'Thanh toán thành công!');
            }
        }

        return redirect()->route('order.failed')
            ->with('error', $result['message']);
    }

    /**
     * Xử lý IPN
     */
    public function vnpayIpn(Request $request)
    {
        $result = VNPay::verifyIpn($request->all());

        if (!$result['is_valid']) {
            return response()->json([
                'RspCode' => '97',
                'Message' => 'Invalid signature'
            ]);
        }

        // Tìm order
        $order = Order::where('txn_ref', $result['txn_ref'])->first();

        if (!$order) {
            return response()->json([
                'RspCode' => '01',
                'Message' => 'Order not found'
            ]);
        }

        // Check amount
        if ($order->total_amount != $result['amount']) {
            return response()->json([
                'RspCode' => '04',
                'Message' => 'Invalid amount'
            ]);
        }

        // Check if already confirmed
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
}
```

### Bước 3: Đăng ký Routes

```php
// routes/web.php
use App\Http\Controllers\PaymentController;

Route::prefix('payment')->group(function () {
    Route::post('/vnpay/create', [PaymentController::class, 'createPayment'])
        ->name('payment.vnpay.create');
    
    Route::get('/vnpay/return', [PaymentController::class, 'vnpayReturn'])
        ->name('payment.vnpay.return');
});

Route::post('/vnpay/ipn', [PaymentController::class, 'vnpayIpn'])
    ->name('payment.vnpay.ipn');
```

### Bước 4: Tạo Migration cho Orders

```bash
php artisan make:migration add_vnpay_fields_to_orders_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('txn_ref')->unique()->nullable()->after('id');
            $table->string('vnpay_transaction_no')->nullable()->after('status');
            $table->string('vnpay_response_code')->nullable()->after('vnpay_transaction_no');
            $table->timestamp('paid_at')->nullable()->after('vnpay_response_code');
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'txn_ref',
                'vnpay_transaction_no',
                'vnpay_response_code',
                'paid_at'
            ]);
        });
    }
};
```

Run migration:

```bash
php artisan migrate
```

## Testing

### Test tạo payment URL

```bash
php artisan vnpay:test --amount=100000
```

### Test trong trình duyệt

1. Tạo một order test
2. Click vào nút thanh toán
3. Sẽ được redirect đến VNPay sandbox
4. Sử dụng thông tin thẻ test (được cung cấp bởi VNPay)
5. Hoàn tất thanh toán
6. Kiểm tra return URL và IPN

### Test IPN với ngrok

Vì VNPay cần call IPN URL public, bạn có thể dùng ngrok:

```bash
# Install ngrok
npm install -g ngrok

# Start ngrok
ngrok http 8000

# Update IPN URL trong .env
VNPAY_IPN_URL=https://your-ngrok-url.ngrok.io/vnpay/ipn
```

## Production Deployment

### 1. Cập nhật môi trường

```env
VNPAY_ENVIRONMENT=production
VNPAY_TMN_CODE=your_production_tmn_code
VNPAY_HASH_SECRET=your_production_hash_secret
VNPAY_RETURN_URL=https://yourdomain.com/vnpay/return
VNPAY_IPN_URL=https://yourdomain.com/vnpay/ipn
```

### 2. SSL Certificate

Đảm bảo website có SSL certificate (HTTPS).

### 3. Whitelist IP

Cấu hình whitelist IP của server trong VNPay merchant portal nếu cần.

### 4. Monitoring

- Enable logging: `VNPAY_LOG_ENABLED=true`
- Monitor log files: `storage/logs/laravel.log`
- Set up alerts cho failed transactions

### 5. Performance

```php
// Queue IPN processing
dispatch(new ProcessVNPayIPN($ipnData))->onQueue('payments');
```

### 6. Security Checklist

- [x] HTTPS enabled
- [x] `.env` không commit vào git
- [x] Hash secret được bảo mật
- [x] Validate checksum từ VNPay
- [x] Rate limiting cho IPN endpoint
- [x] Log mọi transactions
- [x] Monitor suspicious activities

### 7. Backup

Backup định kỳ:
- Database (orders, transactions)
- Log files
- Configuration files

## Troubleshooting

### Lỗi: "Invalid signature"

**Nguyên nhân:** Hash secret không đúng hoặc params bị thay đổi

**Giải pháp:**
1. Kiểm tra `VNPAY_HASH_SECRET` trong .env
2. Clear config: `php artisan config:clear`
3. Không thay đổi params từ VNPay

### Lỗi: "Order not found"

**Nguyên nhân:** txn_ref không tìm thấy trong database

**Giải pháp:**
1. Kiểm tra txn_ref đã lưu đúng chưa
2. Kiểm tra logic tìm order
3. Check logs

### Lỗi: "Connection timeout"

**Nguyên nhân:** Không kết nối được VNPay API

**Giải pháp:**
1. Kiểm tra internet connection
2. Tăng timeout: `VNPAY_TIMEOUT=60`
3. Check firewall rules

### IPN không được gọi

**Nguyên nhân:** URL không public hoặc bị block

**Giải pháp:**
1. Đảm bảo IPN URL là public và accessible
2. Check server logs
3. Test với ngrok
4. Kiểm tra firewall

## Support

Nếu gặp vấn đề:

1. Check [README.md](README.md)
2. Xem [GitHub Issues](https://github.com/anhtuan92na/laravel-vnpay-payment/issues)
3. Contact: anhtuan92na@gmail.com

## Next Steps

Sau khi cài đặt thành công:

1. Tùy chỉnh views cho success/failed pages
2. Implement notification cho customer
3. Set up monitoring và alerts
4. Test thoroughly trước khi go live
5. Prepare rollback plan

Chúc bạn tích hợp thành công! 🎉