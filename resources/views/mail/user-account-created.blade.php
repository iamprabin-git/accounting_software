<x-mail::message>
# {{ __('Account created') }}

{{ __('Hello :name,', ['name' => $user->name]) }}

{{ __('Your account on :app is ready. You can sign in using this email address:', ['app' => config('app.name')]) }}

**{{ $user->email }}**

{{ __('If you did not expect this message, you can ignore it or contact support.') }}

{{ __('Thanks,') }}<br>
{{ config('app.name') }}
</x-mail::message>
