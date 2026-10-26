# shopwise-notifications

Transactional emails for Shopwise. Consumes domain events from Kafka, logs each mail in `mail_log` and sends it
through a queued job (SMTP → Mailpit locally, http://localhost:8025).

Run it from `shopwise-platform`: `tilt up -- --only=identity,notifications`, register a customer, then open Mailpit.

Conventions and service facts: `AGENTS.md`.
