<x-mail::message>
# Hola, {{ $userName }}

Gracias por registrarte en {{ config('app.name') }}.

<x-mail::button :url="config('app.url')">
    Ir a la aplicación
</x-mail::button>

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
