<?php

namespace VNPayPayment\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use VNPayPayment\Facades\VNPay;

class VerifyVNPayIpnIp
{
    /**
     * Handle an incoming request.
     *
     * Kiểm tra IP của request IPN có nằm trong whitelist không.
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $clientIp = $this->getClientIp($request);

        // Log IPN request
        VNPay::logIpnReceived($clientIp, $request->all(), VNPay::isAllowedIpnIp($clientIp));

        // Kiểm tra IP
        if (!VNPay::isAllowedIpnIp($clientIp)) {
            return response()->json([
                'RspCode' => '99',
                'Message' => 'IP not allowed: ' . $clientIp,
            ], 403);
        }

        return $next($request);
    }

    /**
     * Lấy IP thực của client (xử lý các trường hợp qua proxy/load balancer)
     *
     * @param Request $request
     * @return string
     */
    protected function getClientIp(Request $request): string
    {
        // Các header phổ biến chứa IP thực khi qua proxy
        $headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_X_FORWARDED_FOR',      // Standard proxy header
            'HTTP_X_REAL_IP',            // Nginx proxy
            'HTTP_CLIENT_IP',            // Some proxies
            'REMOTE_ADDR',               // Direct connection
        ];

        foreach ($headers as $header) {
            $ip = $request->server($header);
            if ($ip) {
                // X-Forwarded-For có thể chứa nhiều IP, lấy IP đầu tiên
                if (str_contains($ip, ',')) {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }
                // Validate IP format
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return $request->ip() ?? '0.0.0.0';
    }
}
