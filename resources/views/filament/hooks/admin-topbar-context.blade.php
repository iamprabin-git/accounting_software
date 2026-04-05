@auth('admin')
    @php
        $admin = auth('admin')->user();
    @endphp
    @if ($admin)
        <div
            class="me-3 hidden flex-col items-end justify-center border-e border-gray-200 pe-4 text-end dark:border-white/10 md:flex"
        >
            <span
                class="text-[10px] font-semibold uppercase tracking-widest text-gray-500 dark:text-gray-400"
            >
                {{ __('Administration') }}
            </span>
            <span class="text-sm font-semibold text-gray-950 dark:text-white">
                {{ config('app.name', 'Ledger') }}
            </span>
            <span class="max-w-[14rem] truncate text-xs text-gray-600 dark:text-gray-300">
                {{ $admin->name }}
            </span>
        </div>
    @endif
@endauth
