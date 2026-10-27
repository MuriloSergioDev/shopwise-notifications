# shopwise-notifications

Transactional emails triggered by other services' events. Created from `shopwise-service-template`: structure,
checks and how to run PHP are described there and still apply.

## Service facts
- **Owns**: `mail_log` (ULID id, `message_id` = envelope id of the causing event, `dedup_key` = business id the mail is about, `template`, `recipient`, `status` `queued`/`sent`/`failed`, `error`, `sent_at`; unique `(template, dedup_key)`), `customers` (read model: `customer_id`, `email`, `name`). Plus Laravel's `jobs`/`failed_jobs` (database queue). Database `notifications`.
- **Publishes**: nothing.
- **Consumes** (consumer `mails`):

  | Event | Mail (template) | Dedup key | Recipient |
  |---|---|---|---|
  | `identity.customer-registered` | `WelcomeMail` (`welcome`) | customerId | event email; also upserts `customers` |
  | `orders.order-placed` | `OrderConfirmationMail` (`order-confirmation`) | orderId | event email; also refreshes `customers.email` |
  | `orders.order-shipped` | `OrderShippedMail` (`order-shipped`) | orderId | event email |
  | `payments.refunded` | `RefundMail` (`refund`) | paymentId | `customers` by customerId; unknown customer → warning log, no mail |
- **HTTP**: none besides `/up`.
- **REST dependencies**: none. SMTP: Mailpit in the cluster (chart sets `MAIL_MAILER=smtp`, `MAIL_HOST=mailpit`, `MAIL_PORT=1025`).
- **Runtime**: consumer `mails`, scheduler, queue worker (`queue.enabled` in `deploy/values.yaml`; without it mails stay `queued`).
- **Local URL**: http://localhost:8109 (Tilt port-forward); mails at http://localhost:8025.

## How a mail goes out
1. The kit's consumer runs the handler in one transaction with the `processed_messages` insert (transport dedup by envelope id).
2. The handler builds a mailable and calls `App\Actions\QueueMail`: `insertOrIgnore` into `mail_log` (business dedup by `(template, dedup_key)`), then `SendMail::dispatch(...)->afterCommit()`. A rolled-back event sends nothing.
   The business dedup matters: per ADR-0004 a repeated command makes orders/payments re-publish the same outcome under a **new** envelope id.
3. `App\Jobs\SendMail` (database queue, 4 tries, backoff 10/60/300 s) skips a log already `sent`, sends synchronously, marks `sent`; `failed()` marks `failed` with the error. A crash between SMTP accepting and the status update can still resend: at-least-once, like any SMTP sender.

## Adding an email
1. Event + generated DTO must exist in `shopwise-contracts` first; `composer update shopwise/contracts`.
2. `app/Mail/<Name>Mail.php`: `final`, extends `Mailable`, implements `App\Mail\TemplatedMail` (stable `template()` name), markdown view in `resources/views/mail/`. Constructor takes plain values or `App\Data` DTOs only (it is serialized into the job). Money: `App\Data\Amount` (`USD 52.20`).
3. `app/Messaging/Handlers/<Event>Handler.php`: parse the event → `QueueMail::handle(messageId: $envelope->id, dedupKey: <business id>, recipient: ..., mail: new <Name>Mail(...))`. No email in the event → look it up in `customers`.
4. Map the type in `config/messaging.php` and add the topic to the `mails` consumer in `deploy/values.yaml`.
5. Test: feed the contract example twice and once re-published under a new id, assert one mail (see `tests/Feature/Messaging/OrderMailsTest.php`).

## Gotchas
- `orders.order-placed` is parsed by `App\Data\PlacedOrder`, not the generated `OrderPlacedV1`: the generator types nullable required fields (`couponCode`) as non-nullable `string`, so `fromArray` throws a TypeError on an order without a coupon.
- Identity's seeder creates customers without publishing `customer-registered`, so seeded customers are unknown here until they place an order; a refund for such a customer is skipped (logged).

## Rules (enforced in `tests/ArchTest.php`)
- Only `App\Jobs` touches the `Mail` facade; handlers never dispatch jobs themselves, they go through `QueueMail`.
- Every class in `App\Mail` implements `TemplatedMail`.

## Running things
- No host PHP: `docker run --rm -v "$(pwd -W):/app" -w /app shopwise-php:base composer check`.
- Tests run on SQLite by default; run them on Postgres too before pushing (see the workspace `PROGRESS.md`).
- In the cluster: `kubectl exec deploy/notifications-http -- php artisan queue:failed` to see failed sends.
