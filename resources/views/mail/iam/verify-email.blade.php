
<x-mail::message>
# Hola, {{ $userName }}

Gracias por registrarte.

Para verificar tu dirección de correo electrónico, pulsa el siguiente botón:

<x-mail::button :url="$url">
    Verificar mi correo electrónico
</x-mail::button>

Si no has creado esta cuenta, puedes ignorar este mensaje.

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
