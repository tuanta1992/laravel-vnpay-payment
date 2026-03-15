# Laravel VNPay Payment Gateway

[![Latest Version on Packagist](https://img.shields.io/packagist/v/tuanta1992/laravel-vnpay-payment.svg?style=flat-square)](https://packagist.org/packages/tuanta1992/laravel-vnpay-payment)
[![Total Downloads](https://img.shields.io/packagist/dt/tuanta1992/laravel-vnpay-payment.svg?style=flat-square)](https://packagist.org/packages/tuanta1992/laravel-vnpay-payment)
[![License](https://img.shields.io/packagist/l/tuanta1992/laravel-vnpay-payment.svg?style=flat-square)](LICENSE.md)

A Laravel package for integrating [VNPay](https://vnpay.vn) - one of Vietnam's leading online payment gateways.

## Features

- Create VNPay payment URLs
- Verify Return URL & IPN (Instant Payment Notification)
- Query transaction status
- Refund transactions (full & partial)
- Multiple payment methods (ATM, QR Code, International Cards)
- Dual parameter format: simplified (snake_case) or native VNPay (`vnp_*`)
- Artisan commands for testing & status checks
- Configurable logging
- Laravel 10+ / PHP 8.1+

## Requirements

- PHP >= 8.1
- Laravel >= 10.0

## Installation

```bash
composer require tuanta1992/laravel-vnpay-payment
```

Publish the config file:

```bash
php artisan vendor:publish --tag=vnpay-config
```

## Configuration

Add the following to your `.env` file:

```env
VNPAY_TMN_CODE=your_tmn_code
VNPAY_HASH_SECRET=your_hash_secret
VNPAY_ENVIRONMENT=sandbox
VNPAY_RETURN_URL="${APP_URL}/vnpay/return"
VNPAY_IPN_URL="${APP_URL}/vnpay/ipn"
VNPAY_LOCALE=vn
VNPAY_EXPIRE_TIME=15
VNPAY_LOG_ENABLED=true
VNPAY_TIMEOUT=30
```

> `VNPAY_TMN_CODE` and `VNPAY_HASH_SECRET` are provided by VNPay when you register as a merchant.

Verify your configuration:

```bash
php artisan vnpay:status
```

## Usage

### Create Payment URL

```php
use VNPayPayment\Facades\VNPay;

$paymentUrl = VNPay::createPaymentUrl([
    'txn_ref' => 'ORDER_' . time(),
    'amount' => 100000, // 100,000 VND
    'order_info' => 'Thanh toan don hang #123',
    'order_type' => 'billpayment',
    'bank_code' => 'VNBANK', // optional
]);

return redirect($paymentUrl);
```

**Parameters:**

| Parameter | Required | Description |
|-----------|----------|-------------|
| `txn_ref` | Yes | Unique transaction reference |
| `amount` | Yes | Amount in VND |
| `order_info` | Yes | Order description |
| `order_type` | No | `billpayment`, `topup`, `other` |
| `bank_code` | No | Bank code (see table below) |
| `locale` | No | `vn` or `en` |

### Dual Parameter Format

This package supports two parameter styles:

**Simplified (recommended)** - auto-multiplies amount by 100 and strips Vietnamese accents:

```php
VNPay::createPaymentUrl([
    'txn_ref' => 'ORDER123',
    'amount' => 100000,       // auto becomes 10,000,000
    'order_info' => 'Thanh toan don hang',
]);
```

**Native VNPay** - pass parameters exactly as documented by VNPay:

```php
VNPay::createPaymentUrl([
    'vnp_TxnRef' => 'ORDER123',
    'vnp_Amount' => 10000000,  // pre-formatted
    'vnp_OrderInfo' => 'Thanh toan don hang',
]);
```

> VNPay requires amounts in the smallest currency unit: 100,000 VND = 10,000,000.

### Verify Return URL

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
        // Payment successful
        // $result['txn_ref'], $result['transaction_no'], $result['amount']
        return view('payment.success', compact('result'));
    }

    return view('payment.failed', compact('result'));
}
```

**Return data:** `is_valid`, `is_success`, `txn_ref`, `amount`, `transaction_no`, `response_code`, `message`

### Handle IPN (Instant Payment Notification)

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

### Query Transaction

```php
$result = VNPay::queryTransaction([
    'txn_ref' => 'ORDER123',
    'transaction_date' => '20231225153000', // YmdHis
    'order_info' => 'Query transaction',
]);
```

### Refund Transaction

```php
$result = VNPay::refundTransaction([
    'txn_ref' => 'ORDER123',
    'amount' => 100000,
    'transaction_type' => '02', // 02: full, 03: partial
    'transaction_date' => '20231225153000',
    'create_by' => 'admin@example.com',
    'order_info' => 'Refund for order #123',
]);
```

### Helper Methods

```php
$bankCodes = VNPay::getBankCodes();
$bankName  = VNPay::getBankName('VIETCOMBANK');
$message   = VNPay::getResponseMessage('00');
$isSuccess = VNPay::isSuccess('00', '00');
```

## Artisan Commands

```bash
# Test creating a payment URL
php artisan vnpay:test --amount=100000 --txn-ref=TEST123

# Check configuration status
php artisan vnpay:status
```

## Bank Codes

| Code | Name |
|------|------|
| `VNPAYQR` | VNPay QR |
| `VNBANK` | Domestic ATM / Bank account |
| `INTCARD` | International card |
| `VIETCOMBANK` | Vietcombank |
| `VIETINBANK` | VietinBank |
| `BIDV` | BIDV |
| `AGRIBANK` | Agribank |
| `SACOMBANK` | Sacombank |
| `TECHCOMBANK` | Techcombank |
| `ACB` | ACB |
| `VPBANK` | VPBank |
| `TPBANK` | TPBank |
| `MBBANK` | MBBank |
| `NCB` | NCB |

## Response Codes

| Code | Meaning |
|------|---------|
| `00` | Transaction successful |
| `05` | Insufficient balance |
| `06` | Wrong OTP |
| `07` | Suspicious transaction |
| `09` | Internet Banking not registered |
| `10` | Authentication failed 3+ times |
| `11` | Payment timeout |
| `12` | Card/Account locked |
| `24` | Transaction cancelled by customer |
| `65` | Transaction limit exceeded |
| `75` | Bank under maintenance |
| `79` | Wrong password too many times |
| `99` | Unknown error |

## Transaction Status Codes

| Code | Meaning |
|------|---------|
| `00` | Successful |
| `01` | Incomplete |
| `02` | Error |
| `04` | Reversed |
| `05` | Processing refund |
| `06` | Refund request sent |
| `07` | Suspected fraud |
| `08` | Payment timeout |
| `09` | Refund rejected |

## Troubleshooting

**Invalid signature** - Check `VNPAY_HASH_SECRET` in `.env`, then run `php artisan config:clear`.

**Order not found** - Verify `txn_ref` is stored correctly. VNPay may call IPN multiple times.

**Connection timeout** - Increase `VNPAY_TIMEOUT` in `.env` or check network connectivity to VNPay.

## Security

- Always verify checksums from VNPay
- Never expose `VNPAY_HASH_SECRET`
- Use HTTPS in production
- Validate amount and order info
- Apply rate limiting to the IPN endpoint

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for recent changes.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

The MIT License (MIT). See [LICENSE](LICENSE.md) for more information.
