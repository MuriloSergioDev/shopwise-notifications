<?php

declare(strict_types=1);

use App\Actions\QueueMail;
use App\Enums\MailStatus;
use App\Jobs\SendMail;
use App\Mail\WelcomeMail;
use App\Models\Customer;
use App\Models\MailLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Shopwise\Kit\Messaging\DispatchResult;
use Shopwise\Kit\Messaging\Envelope;
use Shopwise\Kit\Messaging\MessageDispatcher;

it('sends one welcome mail when the event is delivered twice', function (): void {
    Mail::fake();
    $envelope = contractExample('identity.customer-registered.v1');
    $dispatcher = app(MessageDispatcher::class);

    expect($dispatcher->dispatch($envelope, 'identity.customer-registered'))->toBe(DispatchResult::Handled)
        ->and($dispatcher->dispatch($envelope, 'identity.customer-registered'))->toBe(DispatchResult::Duplicate);

    Mail::assertSentCount(1);
    Mail::assertSent(WelcomeMail::class, fn (WelcomeMail $mail): bool => $mail->hasTo('ada@example.com') && $mail->name === 'Ada Lovelace');

    $log = MailLog::query()->sole();
    expect($log->message_id)->toBe('01JBCDEFGHJKMNPQRSTVWXYZ10')
        ->and($log->dedup_key)->toBe('01JBCDEFGHJKMNPQRSTVWXYZ11')
        ->and($log->template)->toBe('welcome')
        ->and($log->recipient)->toBe('ada@example.com')
        ->and($log->status)->toBe(MailStatus::Sent)
        ->and($log->sent_at)->not->toBeNull();
});

it('does not send again when the event gets past the processed_messages check', function (): void {
    Mail::fake();
    $envelope = contractExample('identity.customer-registered.v1');
    $dispatcher = app(MessageDispatcher::class);
    $dispatcher->dispatch($envelope, 'identity.customer-registered');

    // E.g. the dedup table was pruned: mail_log is the second line of defence.
    DB::table('processed_messages')->delete();

    expect($dispatcher->dispatch($envelope, 'identity.customer-registered'))->toBe(DispatchResult::Handled);
    Mail::assertSentCount(1);
    expect(MailLog::query()->count())->toBe(1);
});

it('welcomes each registration separately', function (): void {
    Mail::fake();
    $dispatcher = app(MessageDispatcher::class);
    $dispatcher->dispatch(contractExample('identity.customer-registered.v1'), 'identity.customer-registered');

    $other = contractExample('identity.customer-registered.v1', ['customerId' => '01JBCDEFGHJKMNPQRSTVWXYZ21', 'email' => 'grace@example.com', 'name' => 'Grace Hopper']);
    $dispatcher->dispatch(Envelope::fromArray([...$other->toArray(), 'id' => '01JBCDEFGHJKMNPQRSTVWXYZ20']), 'identity.customer-registered');

    Mail::assertSentCount(2);
    Mail::assertSent(WelcomeMail::class, fn (WelcomeMail $mail): bool => $mail->hasTo('grace@example.com'));
});

it('queues the mail instead of sending it while handling the event', function (): void {
    Queue::fake();
    Mail::fake();

    app(MessageDispatcher::class)->dispatch(contractExample('identity.customer-registered.v1'), 'identity.customer-registered');

    Mail::assertNothingSent();
    Queue::assertPushed(SendMail::class, 1);
    expect(MailLog::query()->sole()->status)->toBe(MailStatus::Queued);
});

it('sends nothing when the handling transaction rolls back', function (): void {
    Mail::fake();

    try {
        DB::transaction(function (): void {
            app(QueueMail::class)->handle('01JBCDEFGHJKMNPQRSTVWXYZ30', '01JBCDEFGHJKMNPQRSTVWXYZ11', 'ada@example.com', new WelcomeMail('Ada'));

            throw new RuntimeException('handler failed after queueing');
        });
    } catch (RuntimeException) {
    }

    Mail::assertNothingSent();
    expect(MailLog::query()->count())->toBe(0);
});

it('welcomes a customer once even when identity re-publishes the registration under a new id', function (): void {
    Mail::fake();
    $dispatcher = app(MessageDispatcher::class);
    $envelope = contractExample('identity.customer-registered.v1');
    $dispatcher->dispatch($envelope, 'identity.customer-registered');

    $republished = Envelope::fromArray([...$envelope->toArray(), 'id' => '01JBCDEFGHJKMNPQRSTVWXYZ22']);

    expect($dispatcher->dispatch($republished, 'identity.customer-registered'))->toBe(DispatchResult::Handled);
    Mail::assertSentCount(1);
});

it('remembers the customer for mails whose events carry no email', function (): void {
    Mail::fake();

    app(MessageDispatcher::class)->dispatch(contractExample('identity.customer-registered.v1'), 'identity.customer-registered');

    $customer = Customer::query()->findOrFail('01JBCDEFGHJKMNPQRSTVWXYZ11');
    expect($customer->email)->toBe('ada@example.com')
        ->and($customer->name)->toBe('Ada Lovelace');
});
