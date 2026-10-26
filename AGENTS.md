# shopwise-notifications

Transactional emails triggered by other services' events. Created from `shopwise-service-template`: structure,
checks and how to run PHP are described there and still apply.

## Service facts
- **Owns**: `mail_log` (ULID id, `message_id` = envelope id of the causing event, `template`, `recipient`, `status` `queued`/`sent`/`failed`, `error`, `sent_at`; unique `(message_id, template)`). Plus Laravel's `jobs`/`failed_jobs` (database queue). Database `notifications`.
- **Publishes**: nothing.
- **Consumes** (consumer `customer-mails`): `identity.customer-registered` → `WelcomeMail` (template `welcome`). ADR-0001 also lists `orders.*` and `payments.refunded` for later.
- **HTTP**: none besides `/up`.
- **REST dependencies**: none. SMTP: Mailpit in the cluster (chart sets `MAIL_MAILER=smtp`, `MAIL_HOST=mailpit`, `MAIL_PORT=1025`).
- **Local URL**: http://localhost:8109 (Tilt port-forward); mails at http://localhost:8025.

## How a mail goes out
1. The kit's consumer runs the handler in one transaction with the `processed_messages` insert (first dedup).
2. The handler builds a mailable and calls `App\Actions\QueueMail`: `insertOrIgnore` into `mail_log` (second dedup, by `(message_id, template)`), then `SendMail::dispatch(...)->afterCommit()`. A rolled-back event sends nothing.
3. `App\Jobs\SendMail` (database queue, 4 tries, backoff 10/60/300 s) skips a log already `sent`, sends synchronously, marks `sent`; `failed()` marks `failed` with the error. A crash between SMTP accepting and the status update can still resend: at-least-once, like any SMTP sender.

## Adding an email (e.g. `orders.order-placed`)
1. Event + generated DTO must exist in `shopwise-contracts` first; bump `shopwise/contracts`.
2. `app/Mail/<Name>Mail.php`: `final`, extends `Mailable`, implements `App\Mail\TemplatedMail` (stable `template()` name), markdown view in `resources/views/mail/`. Constructor takes plain values only (it is serialized into the job).
3. `app/Messaging/Handlers/<Event>Handler.php`: `<Event>V1::fromArray($envelope->data)` → `QueueMail::handle($envelope->id, $recipient, new <Name>Mail(...))`.
4. Map the type in `config/messaging.php` and add the topic to a consumer in `deploy/values.yaml`.
5. Test: feed the contract example twice, assert one mail (see `tests/Feature/Messaging/CustomerRegisteredHandlerTest.php`).
Events that only carry a customer id (no email) need a customer read model here first: copy email/name from `identity.customer-registered` rather than calling identity.

## Rules (enforced in `tests/ArchTest.php`)
- Only `App\Jobs` touches the `Mail` facade; handlers never dispatch jobs themselves, they go through `QueueMail`.
- Every class in `App\Mail` implements `TemplatedMail`.

## Running things
- No host PHP: `docker run --rm -v "$(pwd -W):/app" -w /app shopwise-php:base composer check`.
- Tests run on SQLite by default; run them on Postgres too before pushing (see the workspace `PROGRESS.md`).
- In the cluster: `kubectl exec deploy/notifications-http -- php artisan queue:failed` to see failed sends.
