@if (Route::has('superadmin.error-tracker.index'))
    @livewire('sidebar-menu-item', [
        'name' => 'Error Tracker',
        'icon' => 'errortracker',
        'customIcon' => '<svg class="w-6 h-6 transition duration-75 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/></svg>',
        'link' => route('superadmin.error-tracker.index'),
        'active' => request()->routeIs('superadmin.error-tracker.*'),
    ], key('sidebar-errortracker'))
@endif
