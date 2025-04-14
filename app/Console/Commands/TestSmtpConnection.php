<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class TestSmtpConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:smtp
                            {--recipient= : The email address to send the test email to.}
                            {--retry-delay=5 : Seconds to wait before retrying after a failure.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the SMTP connection by attempting to send an email via configured mailer.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $recipient = $this->option('recipient');
        if (!$recipient) {
            // Prompt for recipient if not provided via option
            $recipient = $this->ask('Enter the recipient email address for the test email');
            if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                $this->error('Invalid email address provided.');
                return self::FAILURE;
            }
        }

        $retryDelay = (int) $this->option('retry-delay');
        $attempt = 0;

        $this->info("Attempting to send a test email to {$recipient} using the '{$this->getDefaultMailer()}' mailer...");
        $this->comment("Configuration:");
        $this->comment(" MAILER: " . config('mail.default'));
        $this->comment(" HOST: " . config('mail.mailers.smtp.host'));
        $this->comment(" PORT: " . config('mail.mailers.smtp.port'));
        $this->comment(" USERNAME: " . config('mail.mailers.smtp.username'));
        $this->comment(" ENCRYPTION: " . (config('mail.mailers.smtp.encryption') ?? 'null'));
        $this->comment(" FROM ADDRESS: " . config('mail.from.address'));
        $this->comment(" FROM NAME: " . config('mail.from.name'));
        $this->newLine();

        while (true) {
            $attempt++;
            $this->line("Attempt #{$attempt}: Sending email...");

            try {
                Mail::raw('This is a test email sent from the Laravel TestSmtpConnection command.', function ($message) use ($recipient) {
                    $message->to($recipient)
                            ->subject('SMTP Connection Test');
                });

                $this->info('✅ Email sent successfully!');
                return self::SUCCESS;

            } catch (TransportExceptionInterface $e) {
                $this->error("❌ Failed to send email: {$e->getMessage()}");
                $this->warn("Retrying in {$retryDelay} seconds... (Press Ctrl+C to stop)");
                sleep($retryDelay);
            } catch (\Exception $e) {
                $this->error("❌ An unexpected error occurred: {$e->getMessage()}");
                $this->error("Check your mail configuration and logs for more details.");
                return self::FAILURE; // Stop on unexpected errors
            }
        }
    }

    /**
     * Get the default mailer name.
     */
    private function getDefaultMailer(): string
    {
        return config('mail.default', 'smtp');
    }
}
