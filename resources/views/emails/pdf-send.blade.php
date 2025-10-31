@component('mail::message')
#   {{ $form['document'] }}

Se ha generado un nuevo documento y se ha enviado a su correo electrónico.

Gracias,<br>
{{ config('app.name') }}
@endcomponent