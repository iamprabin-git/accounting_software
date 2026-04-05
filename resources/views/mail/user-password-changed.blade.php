<x-mail::message>
# {{ __('Password changed') }}

{{ __('Hello :name,', ['name' => $user->name]) }}

{{ __('The password for your :app account was changed.', ['app' => config('app.name')]) }}

@if($ipAddress)
**{{ __('IP address') }}:** {{ $ipAddress }}
@endif

{{ __('If you did not make this change, reset your password immediately and contact support.') }}

{{ __('Thanks,') }}<br>
{{ config('app.name') }}
</x-mail::message>
