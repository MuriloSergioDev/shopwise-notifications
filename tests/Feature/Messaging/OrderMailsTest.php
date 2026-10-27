<?php

declare(strict_types=1);

use App\Enums\MailStatus;
use App\Mail\OrderConfirmationMail;
use App\Mail\OrderShippedMail;
use App\Mail\RefundMail;
use App\Models\Customer;
use App\Models\MailLog;
use Illuminate\Support\Facades\Mail;
use Shopwise\Kit\Messaging\DispatchResult;
use Shopwise\Kit\Messaging\Envelope;
use Shopwise\Kit\Messaging\MessageDispatcher;

/**
 * The same event as the contract example under a new envelope id, as producers re-publish an outcome
 * when a command is repeated (ADR-0004).
 *
 * @param  array<string, mixed>  $data
 */
function republished(string $typeAndVersion, string $id, array $data = []): Envelope
{
    return Envelope::fromArray([...contractExample($typeAndVersion, $data)->toArray(), 'id' => $id]);
}

describe('orders.order-placed', function (): void {
    it('confirms an order once, however often it is delivered or re-published', function (): void {
        Mail::fake();
        $dispatcher = app(MessageDispatcher::class);
        $envelope = contractExample('orders.order-placed.v1');

        expect($dispatcher->dispatch($envelope, 'orders.order-placed'))->toBe(DispatchResult::Handled)
            ->and($dispatcher->dispatch($envelope, 'orders.order-placed'))->toBe(DispatchResult::Duplicate)
            ->and($dispatcher->dispatch(republished('orders.order-placed.v1', '01JBCDEFGHJKMNPQRSTVWXYZ70'), 'orders.order-placed'))
            ->toBe(DispatchResult::Handled);

        Mail::assertSentCount(1);
        Mail::assertSent(OrderConfirmationMail::class, fn (OrderConfirmationMail $mail): bool => $mail->hasTo('ada@example.com')
            && $mail->order->number === 'SW-000123');

        $log = MailLog::query()->sole();
        expect($log->template)->toBe('order-confirmation')
            ->and($log->dedup_key)->toBe('01JBCDEFGHJKMNPQRSTVWXYZ44')
            ->and($log->status)->toBe(MailStatus::Sent);
    });

    it('lists lines, totals, coupon and shipping address', function (): void {
        Mail::fake();
        app(MessageDispatcher::class)->dispatch(contractExample('orders.order-placed.v1'), 'orders.order-placed');

        Mail::assertSent(OrderConfirmationMail::class, function (OrderConfirmationMail $mail): bool {
            $mail->assertHasSubject('Your Shopwise order SW-000123');
            $mail->assertSeeInOrderInHtml([
                'Thanks for your order, Ada Lovelace!',
                'Merino Tee (Black / M)', '2', 'USD 29.00', 'USD 58.00',
                'Subtotal: USD 58.00',
                'Discount (WELCOME10): -USD 5.80',
                'Total: USD 52.20',
                '12 St James', 'SW1Y 4JH London', 'GB',
            ]);

            return true;
        });
    });

    it('confirms an order without coupon, discount or product name', function (): void {
        Mail::fake();
        $envelope = contractExample('orders.order-placed.v1', [
            'couponCode' => null,
            'discount' => 0,
            'total' => 5800,
            'lines' => [['sku' => 'TEE-MER-BLK-M', 'productName' => null, 'variantName' => 'Black / M', 'quantity' => 2, 'unitAmount' => 2900, 'lineAmount' => 5800]],
        ]);

        app(MessageDispatcher::class)->dispatch($envelope, 'orders.order-placed');

        Mail::assertSent(OrderConfirmationMail::class, function (OrderConfirmationMail $mail): bool {
            $mail->assertSeeInHtml('Black / M');
            $mail->assertDontSeeInHtml('Discount');
            $mail->assertSeeInHtml('Total: USD 58.00');

            return true;
        });
    });

    it('records the customer email so a later refund can be mailed', function (): void {
        Mail::fake();
        app(MessageDispatcher::class)->dispatch(contractExample('orders.order-placed.v1'), 'orders.order-placed');

        expect(Customer::query()->findOrFail('01JBCDEFGHJKMNPQRSTVWXYZ11')->email)->toBe('ada@example.com');
    });

    it('keeps the registered name when an order refreshes the email', function (): void {
        Mail::fake();
        Customer::factory()->create(['customer_id' => '01JBCDEFGHJKMNPQRSTVWXYZ11', 'email' => 'old@example.com', 'name' => 'Ada Lovelace']);

        app(MessageDispatcher::class)->dispatch(contractExample('orders.order-placed.v1'), 'orders.order-placed');

        $customer = Customer::query()->findOrFail('01JBCDEFGHJKMNPQRSTVWXYZ11');
        expect($customer->email)->toBe('ada@example.com')
            ->and($customer->name)->toBe('Ada Lovelace');
    });
});

describe('orders.order-shipped', function (): void {
    it('mails the tracking details once per order', function (): void {
        Mail::fake();
        $dispatcher = app(MessageDispatcher::class);
        $envelope = contractExample('orders.order-shipped.v1');

        $dispatcher->dispatch($envelope, 'orders.order-shipped');
        $dispatcher->dispatch($envelope, 'orders.order-shipped');
        $dispatcher->dispatch(republished('orders.order-shipped.v1', '01JBCDEFGHJKMNPQRSTVWXYZ71'), 'orders.order-shipped');

        Mail::assertSentCount(1);
        Mail::assertSent(OrderShippedMail::class, function (OrderShippedMail $mail): bool {
            $mail->assertHasTo('ada@example.com');
            $mail->assertHasSubject('Your Shopwise order SW-000123 is on its way');
            $mail->assertSeeInOrderInHtml(['UPS', '1Z999AA10123456784']);

            return true;
        });
        expect(MailLog::query()->sole()->template)->toBe('order-shipped');
    });
});

describe('payments.refunded', function (): void {
    it('mails a known customer once per payment', function (): void {
        Mail::fake();
        Customer::factory()->create(['customer_id' => '01JBCDEFGHJKMNPQRSTVWXYZ11', 'email' => 'ada@example.com', 'name' => 'Ada Lovelace']);
        $dispatcher = app(MessageDispatcher::class);
        $envelope = contractExample('payments.refunded.v1');

        $dispatcher->dispatch($envelope, 'payments.refunded');
        $dispatcher->dispatch($envelope, 'payments.refunded');
        $dispatcher->dispatch(republished('payments.refunded.v1', '01JBCDEFGHJKMNPQRSTVWXYZ72'), 'payments.refunded');

        Mail::assertSentCount(1);
        Mail::assertSent(RefundMail::class, function (RefundMail $mail): bool {
            $mail->assertHasTo('ada@example.com');
            $mail->assertSeeInOrderInHtml(['Hi Ada Lovelace,', 'USD 52.20', 'we could not place your order']);

            return true;
        });

        $log = MailLog::query()->sole();
        expect($log->template)->toBe('refund')
            ->and($log->dedup_key)->toBe('01JBCDEFGHJKMNPQRSTVWXYZ43');
    });

    it('reaches a customer known only from the registration event', function (): void {
        Mail::fake();
        $dispatcher = app(MessageDispatcher::class);
        $dispatcher->dispatch(contractExample('identity.customer-registered.v1'), 'identity.customer-registered');

        $dispatcher->dispatch(contractExample('payments.refunded.v1', ['reason' => 'reservation_expired']), 'payments.refunded');

        Mail::assertSent(RefundMail::class, fn (RefundMail $mail): bool => $mail->hasTo('ada@example.com') && $mail->reason === 'reservation_expired');
    });

    it('skips the mail for an unknown customer without failing the message', function (): void {
        Mail::fake();

        expect(app(MessageDispatcher::class)->dispatch(contractExample('payments.refunded.v1'), 'payments.refunded'))
            ->toBe(DispatchResult::Handled);

        Mail::assertNothingSent();
        expect(MailLog::query()->count())->toBe(0);
    });
});
