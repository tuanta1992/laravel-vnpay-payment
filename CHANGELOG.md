# Changelog

All notable changes to `laravel-vnpay-payment` will be documented in this file.

## [1.0.0] - 2024-01-01

### Added
- Initial release
- Create payment URL functionality
- Return URL verification
- IPN (Instant Payment Notification) handling
- Query transaction API
- Refund transaction API (full and partial)
- Multiple payment methods support (ATM, QR, International Card)
- Detailed logging
- Command line tools (`vnpay:status`, `vnpay:test`)
- Comprehensive documentation
- Support for Laravel 10+
- Support for PHP 8.1+

### Features
- HMAC SHA512 secure hash
- Vietnamese accent removal for order info
- Bank code mapping
- Response code mapping
- Transaction status mapping
- Configurable environment (sandbox/production)
- Customizable timeout
- IP address detection
- Date/time formatting helpers

### Security
- Secure hash verification
- Input validation
- Required parameter checking
- Checksum validation for all callbacks

## [Unreleased]

### Planned
- Batch refund support
- Webhook events
- Additional payment methods
- Enhanced testing coverage
- Performance optimizations