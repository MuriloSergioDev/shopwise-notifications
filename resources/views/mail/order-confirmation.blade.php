<x-mail::message>
# Thanks for your order, {{ $order->recipientName }}!

Order **{{ $order->number }}** is confirmed. We'll mail you again when it ships.

<x-mail::table>
| Item | Qty | Price | Total |
|:-----|:---:|------:|------:|
@foreach ($order->lines as $line)
| {{ $line->label() }} | {{ $line->quantity }} | {{ $line->unitPrice->formatted() }} | {{ $line->lineTotal->formatted() }} |
@endforeach
</x-mail::table>

Subtotal: {{ $order->subtotal->formatted() }}<br>
@if ($order->discount->minor > 0)
Discount{{ $order->couponCode !== null ? ' ('.$order->couponCode.')' : '' }}: -{{ $order->discount->formatted() }}<br>
@endif
**Total: {{ $order->total->formatted() }}**

**Shipping to**<br>
@foreach ($order->shippingAddress as $addressLine)
{{ $addressLine }}<br>
@endforeach

Thanks,<br>
The Shopwise team
</x-mail::message>
