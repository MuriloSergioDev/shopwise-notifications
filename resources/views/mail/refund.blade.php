<x-mail::message>
# {{ $customerName !== null ? 'Hi '.$customerName.',' : 'Hi,' }}

@if ($checkoutFailed)
We refunded **{{ $amount->formatted() }}** to your payment method because {{ $explanation }}.
@else
We refunded **{{ $amount->formatted() }}** of your payment to your payment method.
@endif
Depending on your bank, it can take a few days to show up.

@if ($checkoutFailed)
Nothing was ordered, so you can check out again whenever you like.
@else
If you have questions about this refund, just reply to this email.
@endif

Thanks,<br>
The Shopwise team
</x-mail::message>
