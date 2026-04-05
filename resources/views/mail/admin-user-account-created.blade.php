<x-mail::message>
# {{ __('New user account') }}

{{ __('A new user account was created on :app.', ['app' => config('app.name')]) }}

<x-mail::panel>
**{{ __('Name') }}:** {{ $user->name }}  
**{{ __('Email') }}:** {{ $user->email }}  
**{{ __('Role') }}:** {{ $user->role }}  
**{{ __('User ID') }}:** {{ $user->id }}  
@if($user->company_id)
**{{ __('Company ID') }}:** {{ $user->company_id }}  
@endif
**{{ __('Active') }}:** {{ $user->is_active ? __('Yes') : __('No') }}  
**{{ __('Created at') }}:** {{ $user->created_at?->toIso8601String() ?? '—' }}
</x-mail::panel>

{{ __('This is an automated message for administrators.') }}
</x-mail::message>
