<x-mail::message>
# {{ $senderName }} shared a shopping list with you

You've been sent the **{{ $listName }}** shopping list. Tap the button below to view it and check off items as you shop.

<x-mail::button :url="$url">
View Shopping List
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
