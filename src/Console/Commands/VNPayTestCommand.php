<?php

namespace VNPayPayment\Console\Commands;

use Illuminate\Console\Command;
use VNPayPayment\Facades\VNPay;

class VNPayTestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vnpay:test 
                            {--amount=10000 : Payment amount}
                            {--txn-ref= : Transaction reference}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test VNPay payment URL generation';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Testing VNPay Payment URL Generation');
        $this->info('========================================');

        try {
            $amount = $this->option('amount');
            $txnRef = $this->option('txn-ref') ?: 'TEST' . time();

            $this->info('Parameters:');
            $this->line('  Amount: ' . number_format($amount) . ' VND');
            $this->line('  Transaction Ref: ' . $txnRef);
            $this->newLine();

            $url = VNPay::createPaymentUrl([
                'txn_ref' => $txnRef,
                'amount' => $amount,
                'order_info' => 'Test payment from CLI',
                'order_type' => 'other',
            ]);

            $this->info('✓ Payment URL generated successfully!');
            $this->newLine();
            $this->line('Payment URL:');
            $this->line($url);
            $this->newLine();

            if ($this->confirm('Do you want to open this URL in browser?', false)) {
                if (PHP_OS_FAMILY === 'Darwin') {
                    exec('open ' . escapeshellarg($url));
                } elseif (PHP_OS_FAMILY === 'Windows') {
                    exec('start ' . escapeshellarg($url));
                } elseif (PHP_OS_FAMILY === 'Linux') {
                    exec('xdg-open ' . escapeshellarg($url));
                }
                $this->info('Opening browser...');
            }

            return 0;

        } catch (\Exception $e) {
            $this->error('✗ Test failed: ' . $e->getMessage());
            return 1;
        }
    }
}