<x-mail::message>
# {{ __('User password changed') }}

{{ __('A user password was changed on :app.', ['app' => config('app.name')]) }}

<x-mail::panel>
**{{ __('Name') }}:** {{ $user->name }}  
**{{ __('Email') }}:** {{ $user->email }}  
**{{ __('Role') }}:** {{ $user->role }}  
**{{ __('User ID') }}:** {{ $user->id }}  
@if($user->company_id)
**{{ __('Company ID') }}:** {{ $user->company_id }}  
@endif
@if($ipAddress)
**{{ __('IP address') }}:** {{ $ipAddress }}
@endif
**{{ __('Time') }}:** {{ now()->toIso8601String() }}
</x-mail::panel>

{{ __('This is an automated message for administrators.') }}
</x-mail::message>
