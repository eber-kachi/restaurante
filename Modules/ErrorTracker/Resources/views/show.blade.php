@extends('layouts.app')

@section('content')
@php $ctx = $event->context ?? []; @endphp
<div class="p-4 bg-white block dark:bg-gray-800 dark:border-gray-700">
    <a href="{{ route('superadmin.error-tracker.index') }}" class="text-sm text-gray-500 hover:underline">← Volver</a>

    <div class="flex flex-wrap items-start justify-between gap-3 mt-2 mb-4">
        <div class="min-w-0">
            <h1 class="text-xl font-semibold text-gray-900 dark:text-white break-all">{{ $event->exception_class ?? strtoupper($event->level) }}</h1>
            <p class="text-sm text-gray-500">{{ $event->file }}:{{ $event->line }}</p>
        </div>
        <div class="flex gap-2">
            @if($event->resolved_at)
                <form method="POST" action="{{ route('superadmin.error-tracker.reopen', $event) }}">@csrf
                    <button class="px-3 py-2 text-sm border border-gray-300 rounded-lg dark:text-white">Reabrir</button>
                </form>
            @else
                <form method="POST" action="{{ route('superadmin.error-tracker.resolve', $event) }}">@csrf
                    <button class="px-3 py-2 text-sm text-white bg-green-700 rounded-lg hover:bg-green-600">Marcar resuelto</button>
                </form>
            @endif
            <form method="POST" action="{{ route('superadmin.error-tracker.destroy', $event) }}" onsubmit="return confirm('¿Eliminar este error?')">@csrf @method('DELETE')
                <button class="px-3 py-2 text-sm text-white bg-red-700 rounded-lg hover:bg-red-600">Eliminar</button>
            </form>
        </div>
    </div>

    <pre class="p-3 mb-4 text-sm text-red-900 whitespace-pre-wrap break-words border border-red-200 rounded-lg bg-red-50">{{ $event->message }}</pre>

    <div class="grid gap-3 mb-4 text-sm sm:grid-cols-4">
        <div class="p-3 border border-gray-200 rounded-lg dark:border-gray-700 dark:text-white"><div class="text-xs text-gray-500">Ocurrencias</div><div class="text-lg font-semibold">{{ number_format($event->occurrences) }}</div></div>
        <div class="p-3 border border-gray-200 rounded-lg dark:border-gray-700 dark:text-white"><div class="text-xs text-gray-500">Primera vez</div>{{ $event->first_seen_at }}</div>
        <div class="p-3 border border-gray-200 rounded-lg dark:border-gray-700 dark:text-white"><div class="text-xs text-gray-500">Última vez</div>{{ $event->last_seen_at }}</div>
        <div class="p-3 border border-gray-200 rounded-lg dark:border-gray-700 dark:text-white"><div class="text-xs text-gray-500">Última notificación</div>{{ $event->last_notified_at ?? '—' }}</div>
    </div>

    <h2 class="mb-2 font-semibold text-gray-900 dark:text-white">Stack trace</h2>
    <pre class="p-3 mb-4 overflow-x-auto text-xs leading-5 border border-gray-200 rounded-lg bg-gray-50 dark:bg-gray-900 dark:text-gray-200 dark:border-gray-700">{{ $event->trace }}</pre>

    <h2 class="mb-2 font-semibold text-gray-900 dark:text-white">Contexto (última ocurrencia)</h2>
    <pre class="p-3 overflow-x-auto text-xs leading-5 border border-gray-200 rounded-lg bg-gray-50 dark:bg-gray-900 dark:text-gray-200 dark:border-gray-700">{{ json_encode($ctx, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR) }}</pre>
</div>
@endsection
