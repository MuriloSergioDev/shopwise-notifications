<x-mail::message>
# {{ $customerName !== null ? 'Hi '.$customerName.',' : 'Hi,' }}

We refunded **{{ $amount->formatted() }}** to your payment method because {{ $explanation }}.
Depending on your bank, it can take a few days to show up.

Nothing was ordered, so you can check out again whenever you like.

Thanks,<br>
The Shopwise team
</x-mail::message>
