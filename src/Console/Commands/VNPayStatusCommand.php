<?php

namespace VNPayPayment\Console\Commands;

use Illuminate\Console\Command;

class VNPayStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vnpay:status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check VNPay configuration status';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('VNPay Configuration Status');
        $this->info('========================================');

        $config = config('vnpay');

        // Check TMN Code
        $tmnCode = $config['tmn_code'] ?? '';
        $this->displayStatus('TMN Code', $tmnCode ? substr($tmnCode, 0, 8) . '...' : 'Not set', !empty($tmnCode));

        // Check Hash Secret
        $hashSecret = $config['hash_secret'] ?? '';
        $this->displayStatus('Hash Secret', $hashSecret ? '***' : 'Not set', !empty($hashSecret));

        // Check Environment
        $environment = $config['environment'] ?? 'sandbox';
        $this->displayStatus('Environment', $environment, true);

        // Check URLs
        $paymentUrl = $config['urls'][$environment]['payment'] ?? '';
        $this->displayStatus('Payment URL', $paymentUrl, !empty($paymentUrl));

        $apiUrl = $config['urls'][$environment]['api'] ?? '';
        $this->displayStatus('API URL', $apiUrl, !empty($apiUrl));

        // Check Return URL
        $returnUrl = $config['return_url'] ?? '';
        $this->displayStatus('Return URL', $returnUrl, !empty($returnUrl));

        // Check IPN URL
        $ipnUrl = $config['ipn_url'] ?? '';
        $this->displayStatus('IPN URL', $ipnUrl, !empty($ipnUrl));

        // Check Logging
        $loggingEnabled = $config['logging']['enabled'] ?? false;
        $this->displayStatus('Logging', $loggingEnabled ? 'Enabled' : 'Disabled', true);

        $this->info('========================================');

        // Overall status
        if (empty($tmnCode) || empty($hashSecret)) {
            $this->error('⚠ Configuration incomplete! Please set VNPAY_TMN_CODE and VNPAY_HASH_SECRET in .env');
            return 1;
        }

        $this->info('✓ Configuration looks good!');
        return 0;
    }

    /**
     * Display status line
     *
     * @param string $label
     * @param string $value
     * @param bool $isValid
     * @return void
     */
    protected function displayStatus(string $label, string $value, bool $isValid): void
    {
        $status = $isValid ? '<fg=green>✓</>' : '<fg=red>✗</>';
        $this->line(sprintf('%-20s %-30s %s', $label, $value, $status));
    }
}