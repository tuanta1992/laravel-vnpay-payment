<?php

return [
    /*
    |--------------------------------------------------------------------------
    | VNPay Terminal Code
    |--------------------------------------------------------------------------
    |
    | Mã định danh merchant kết nối (Terminal Id) được VNPAY cung cấp
    |
    */
    'tmn_code' => env('VNPAY_TMN_CODE', ''),

    /*
    |--------------------------------------------------------------------------
    | VNPay Hash Secret
    |--------------------------------------------------------------------------
    |
    | Secret key để tạo checksum, được VNPAY cung cấp
    |
    */
    'hash_secret' => env('VNPAY_HASH_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | VNPay Environment
    |--------------------------------------------------------------------------
    |
    | Môi trường: 'sandbox' hoặc 'live'
    |
    */
    'environment' => env('VNPAY_ENVIRONMENT', 'sandbox'),

    /*
    |--------------------------------------------------------------------------
    | VNPay URLs
    |--------------------------------------------------------------------------
    |
    | URL của VNPay payment gateway theo môi trường
    |
    */
    'urls' => [
        'sandbox' => [
            'payment' => 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html',
            'api' => 'https://sandbox.vnpayment.vn/merchant_webapi/api/transaction',
        ],
        'live' => [
            'payment' => 'https://pay.vnpay.vn/vpcpay.html',
            'api' => 'https://pay.vnpay.vn/merchant_webapi/api/transaction',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | VNPay Return URL
    |--------------------------------------------------------------------------
    |
    | URL mà VNPAY sẽ redirect khách hàng về sau khi thanh toán
    |
    */
    'return_url' => env('VNPAY_RETURN_URL', env('APP_URL') . '/vnpay/return'),

    /*
    |--------------------------------------------------------------------------
    | VNPay IPN URL (Instant Payment Notification)
    |--------------------------------------------------------------------------
    |
    | URL để VNPAY gửi thông báo kết quả giao dịch
    |
    */
    'ipn_url' => env('VNPAY_IPN_URL', env('APP_URL') . '/vnpay/ipn'),

    /*
    |--------------------------------------------------------------------------
    | VNPay Version
    |--------------------------------------------------------------------------
    |
    | Phiên bản API hiện tại
    |
    */
    'version' => '2.1.0',

    /*
    |--------------------------------------------------------------------------
    | Default Payment Options
    |--------------------------------------------------------------------------
    |
    | Cấu hình mặc định cho thanh toán
    |
    */
    'defaults' => [
        'locale' => env('VNPAY_LOCALE', 'vn'), // vn hoặc en
        'currency' => 'VND',
        'order_type' => 'other', // other, billpayment, topup
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Expire Time
    |--------------------------------------------------------------------------
    |
    | Thời gian hết hạn thanh toán (phút)
    |
    */
    'expire_time' => env('VNPAY_EXPIRE_TIME', 15),

    /*
    |--------------------------------------------------------------------------
    | Bank Codes
    |--------------------------------------------------------------------------
    |
    | Danh sách mã ngân hàng hỗ trợ
    |
    */
    'bank_codes' => [
        'VNPAYQR' => 'Thanh toán bằng ứng dụng hỗ trợ VNPAYQR',
        'VNBANK' => 'Thanh toán qua thẻ ATM/Tài khoản nội địa',
        'INTCARD' => 'Thanh toán qua thẻ quốc tế',
        'VIETCOMBANK' => 'Ngân hàng TMCP Ngoại thương Việt Nam',
        'VIETINBANK' => 'Ngân hàng TMCP Công thương Việt Nam',
        'BIDV' => 'Ngân hàng TMCP Đầu tư và Phát triển Việt Nam',
        'AGRIBANK' => 'Ngân hàng Nông nghiệp và Phát triển Nông thôn Việt Nam',
        'SACOMBANK' => 'Ngân hàng TMCP Sài Gòn Thương Tín',
        'TECHCOMBANK' => 'Ngân hàng TMCP Kỹ thương Việt Nam',
        'ACB' => 'Ngân hàng TMCP Á Châu',
        'VPBANK' => 'Ngân hàng TMCP Việt Nam Thịnh Vượng',
        'TPBANK' => 'Ngân hàng TMCP Tiên Phong',
        'MBBANK' => 'Ngân hàng TMCP Quân đội',
        'NCB' => 'Ngân hàng TMCP Quốc Dân',
    ],

    /*
    |--------------------------------------------------------------------------
    | Response Codes
    |--------------------------------------------------------------------------
    |
    | Bảng mã phản hồi từ VNPAY
    |
    */
    'response_codes' => [
        '00' => 'Giao dịch thành công',
        '05' => 'Tài khoản không đủ số dư',
        '06' => 'Sai mật khẩu OTP',
        '07' => 'Giao dịch nghi ngờ',
        '09' => 'Chưa đăng ký InternetBanking',
        '10' => 'Xác thực sai quá 3 lần',
        '11' => 'Hết hạn chờ thanh toán',
        '12' => 'Thẻ/Tài khoản bị khóa',
        '24' => 'Khách hàng hủy giao dịch',
        '65' => 'Vượt quá hạn mức',
        '75' => 'Ngân hàng đang bảo trì',
        '79' => 'Sai mật khẩu quá số lần quy định',
        '99' => 'Lỗi không xác định',
    ],

    /*
    |--------------------------------------------------------------------------
    | Transaction Status Codes
    |--------------------------------------------------------------------------
    |
    | Bảng mã tình trạng giao dịch
    |
    */
    'transaction_status' => [
        '00' => 'Giao dịch thành công',
        '01' => 'Giao dịch chưa hoàn tất',
        '02' => 'Giao dịch bị lỗi',
        '04' => 'Giao dịch đảo (Khách đã trừ tiền nhưng GD chưa thành công)',
        '05' => 'VNPAY đang xử lý giao dịch hoàn tiền',
        '06' => 'VNPAY đã gửi yêu cầu hoàn tiền',
        '07' => 'Giao dịch nghi ngờ gian lận',
        '08' => 'Giao dịch quá thời gian thanh toán',
        '09' => 'Giao dịch hoàn trả bị từ chối',
    ],

    /*
    |--------------------------------------------------------------------------
    | IPN IP Whitelist
    |--------------------------------------------------------------------------
    |
    | Danh sách IP của server VNPAY được phép gửi IPN
    | Bật verify_ip để chỉ chấp nhận IPN từ các IP này
    |
    */
    'ipn' => [
        'verify_ip' => env('VNPAY_IPN_VERIFY_IP', true),
        'allowed_ips' => [
            'sandbox' => [
                '113.160.92.202',
                '202.93.156.34',
                '203.205.17.226',
                '103.220.84.4',
            ],
            'live' => [
                '113.52.45.78',
                '116.97.245.130',
                '42.118.107.252',
                '113.20.97.250',
                '203.171.19.146',
                '103.220.87.4',
                '103.220.86.4',
                '103.220.86.10',
                '103.220.87.10',
                '103.220.86.139',
                '103.220.87.139',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Bật/tắt logging và cấu hình log channel
    | Log sẽ được lưu riêng để dễ dàng theo dõi giao dịch
    |
    */
    'logging' => [
        'enabled' => env('VNPAY_LOG_ENABLED', true),
        'channel' => env('VNPAY_LOG_CHANNEL', 'stack'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Timeout
    |--------------------------------------------------------------------------
    |
    | Timeout cho API requests (giây)
    |
    */
    'timeout' => env('VNPAY_TIMEOUT', 30),
];