<?php

declare(strict_types=1);

use App\Enums\MailStatus;
use App\Jobs\SendMail;
use App\Mail\WelcomeMail;
use App\Models\MailLog;
use Illuminate\Support\Facades\Mail;

it('skips a mail that was already sent', function (): void {
    Mail::fake();
    $log = MailLog::factory()->sent()->create();

    (new SendMail($log->id, new WelcomeMail('Ada')))->handle();

    Mail::assertNothingSent();
});

it('marks the mail failed once every attempt failed', function (): void {
    $log = MailLog::factory()->create();

    (new SendMail($log->id, new WelcomeMail('Ada')))->failed(new RuntimeException('Connection refused'));

    expect($log->refresh()->status)->toBe(MailStatus::Failed)
        ->and($log->error)->toBe('Connection refused');
});

it('keeps a sent mail sent even if a late failure is reported', function (): void {
    $log = MailLog::factory()->sent()->create();

    (new SendMail($log->id, new WelcomeMail('Ada')))->failed(new RuntimeException('timeout'));

    expect($log->refresh()->status)->toBe(MailStatus::Sent);
});

it('greets the customer by name and links to the storefront', function (): void {
    config(['notifications.storefront_url' => 'http://shop.test']);

    $mail = new WelcomeMail('Ada Lovelace');

    $mail->assertHasSubject('Welcome to Shopwise');
    $mail->assertSeeInHtml('Welcome, Ada Lovelace!');
    $mail->assertSeeInHtml('http://shop.test');
});
