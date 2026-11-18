@extends('layouts.app')

@section('content')
<div class="p-4 bg-white block dark:bg-gray-800 dark:border-gray-700">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <div>
            <h1 class="text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white">Error Tracker</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Activo solo en: <strong>{{ implode(', ', config('errortracker.environments')) }}</strong>
                · Entorno actual: <strong>{{ app()->environment() }}</strong>
                @if(app(\Modules\ErrorTracker\Services\ErrorTracker::class)->isActive())
                    <span class="ml-1 px-2 py-0.5 text-xs rounded bg-green-100 text-green-800">capturando</span>
                @else
                    <span class="ml-1 px-2 py-0.5 text-xs rounded bg-gray-100 text-gray-700">inactivo</span>
                @endif
            </p>
        </div>

        @if($events && ($counts['open'] ?? 0) > 0)
            <form method="POST" action="{{ route('superadmin.error-tracker.resolve-all') }}" onsubmit="return confirm('¿Marcar todos los errores abiertos como resueltos?')">
                @csrf
                <button class="px-3 py-2 text-sm text-white bg-gray-800 rounded-lg hover:bg-gray-700">Resolver todos</button>
            </form>
        @endif
    </div>

    @if(!$events)
        <div class="p-4 text-sm text-yellow-800 bg-yellow-50 rounded-lg">
            Falta la tabla <code>error_tracker_events</code>. Ejecuta <code>php artisan module:migrate ErrorTracker</code>.
            Mientras tanto los errores se siguen notificando por email.
        </div>
    @else
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <div class="inline-flex rounded-lg border border-gray-200 dark:border-gray-600 overflow-hidden text-sm">
                @foreach(['open' => 'Abiertos (' . $counts['open'] . ')', 'resolved' => 'Resueltos (' . $counts['resolved'] . ')', 'all' => 'Todos'] as $key => $text)
                    <a href="{{ route('superadmin.error-tracker.index', ['status' => $key, 'q' => $search]) }}"
                       @class(['px-3 py-2', 'bg-gray-800 text-white' => $status === $key, 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' => $status !== $key])>{{ $text }}</a>
                @endforeach
            </div>

            <form method="GET" class="flex gap-2">
                <input type="hidden" name="status" value="{{ $status }}">
                <input type="text" name="q" value="{{ $search }}" placeholder="Buscar mensaje, clase o archivo"
                       class="text-sm border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white w-72">
                <button class="px-3 py-2 text-sm text-white bg-gray-800 rounded-lg">Buscar</button>
            </form>
        </div>

        <div class="overflow-x-auto border border-gray-200 rounded-lg dark:border-gray-700">
            <table class="min-w-full text-sm divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr class="text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-300">
                        <th class="px-4 py-3">Error</th>
                        <th class="px-4 py-3">Origen</th>
                        <th class="px-4 py-3 text-right">Veces</th>
                        <th class="px-4 py-3">Última vez</th>
                        <th class="px-4 py-3">Primera vez</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($events as $event)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-4 py-3 max-w-xl">
                                <a href="{{ route('superadmin.error-tracker.show', $event) }}" class="block">
                                    <span class="font-semibold text-gray-900 dark:text-white">{{ $event->short_class }}</span>
                                    @if($event->resolved_at)<span class="ml-1 px-1.5 py-0.5 text-xs rounded bg-green-100 text-green-800">resuelto</span>@endif
                                    <span class="block text-gray-600 dark:text-gray-300 truncate">{{ \Illuminate\Support\Str::limit($event->message, 160) }}</span>
                                    <span class="block text-xs text-gray-400 truncate">{{ $event->file }}:{{ $event->line }}</span>
                                </a>
                            </td>
                            <td class="px-4 py-3 text-xs">
                                <span @class(['px-2 py-0.5 rounded', 'bg-red-100 text-red-800' => $event->source === 'exception', 'bg-yellow-100 text-yellow-800' => $event->source === 'log'])>
                                    {{ $event->source === 'log' ? 'log ' . $event->level : 'excepción' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right font-mono dark:text-white">{{ number_format($event->occurrences) }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300 whitespace-nowrap" title="{{ $event->last_seen_at }}">{{ $event->last_seen_at?->diffForHumans() }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300 whitespace-nowrap" title="{{ $event->first_seen_at }}">{{ $event->first_seen_at?->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-gray-500">Sin errores 🎉</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $events->links() }}</div>
    @endif
</div>
@endsection
