<x-mail::message>
# Welcome, {{ $name }}!

Your Shopwise account is ready. Browse the catalog, fill your cart and check out whenever you like.

<x-mail::button :url="$shopUrl">
Start shopping
</x-mail::button>

Thanks,<br>
The Shopwise team
</x-mail::message>
