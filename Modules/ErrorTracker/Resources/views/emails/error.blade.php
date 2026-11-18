@php
    $ctx = $p['context'] ?? [];
    $req = $ctx['request'] ?? null;
    $console = $ctx['console'] ?? null;
    $user = $ctx['user'] ?? null;
    $badge = $label === 'Regresión' ? '#b45309' : ($label === 'Nuevo error' ? '#b91c1c' : '#4b5563');
@endphp
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>{{ $title }}</title></head>
<body style="margin:0;padding:24px;background:#f3f4f6;font-family:-apple-system,Segoe UI,Roboto,Arial,sans-serif;color:#111827;">
<div style="max-width:760px;margin:0 auto;background:#ffffff;border-radius:8px;overflow:hidden;border:1px solid #e5e7eb;">
    <div style="background:#111827;color:#ffffff;padding:16px 20px;">
        <span style="display:inline-block;background:{{ $badge }};color:#fff;font-size:12px;font-weight:bold;padding:3px 8px;border-radius:4px;">{{ $label }}</span>
        <span style="font-size:12px;color:#9ca3af;margin-left:8px;">{{ strtoupper($p['level']) }} · {{ $ctx['app']['environment'] ?? '' }} · {{ $ctx['app']['host'] ?? '' }}</span>
        <h2 style="margin:10px 0 0;font-size:18px;word-break:break-word;">{{ $title }}</h2>
    </div>

    <div style="padding:20px;">
        <pre style="white-space:pre-wrap;word-break:break-word;background:#fef2f2;border:1px solid #fecaca;color:#7f1d1d;padding:12px;border-radius:6px;font-size:13px;margin:0 0 16px;">{{ \Illuminate\Support\Str::limit($p['message'], 1500) }}</pre>

        <table style="width:100%;border-collapse:collapse;font-size:13px;">
            @if($p['exception_class'])
                <tr><td style="padding:4px 0;color:#6b7280;width:140px;">Excepción</td><td style="padding:4px 0;"><code>{{ $p['exception_class'] }}</code></td></tr>
            @endif
            <tr><td style="padding:4px 0;color:#6b7280;">Archivo</td><td style="padding:4px 0;"><code>{{ $p['file'] }}:{{ $p['line'] }}</code></td></tr>
            <tr><td style="padding:4px 0;color:#6b7280;">Origen</td><td style="padding:4px 0;">{{ $p['source'] === 'log' ? 'Log (posible error oculto en try/catch)' : 'Excepción' }}</td></tr>
            <tr><td style="padding:4px 0;color:#6b7280;">Ocurrencias</td><td style="padding:4px 0;">{{ $occurrences }}</td></tr>
            <tr><td style="padding:4px 0;color:#6b7280;">Fecha</td><td style="padding:4px 0;">{{ now()->toDateTimeString() }} ({{ config('app.timezone') }})</td></tr>
            @if($req)
                <tr><td style="padding:4px 0;color:#6b7280;">Request</td><td style="padding:4px 0;word-break:break-all;"><strong>{{ $req['method'] }}</strong> {{ $req['url'] }}</td></tr>
                @if(!empty($req['route']))<tr><td style="padding:4px 0;color:#6b7280;">Ruta</td><td style="padding:4px 0;">{{ $req['route'] }}</td></tr>@endif
                <tr><td style="padding:4px 0;color:#6b7280;">IP</td><td style="padding:4px 0;">{{ $req['ip'] }}</td></tr>
            @endif
            @if(!empty($ctx['livewire']))
                @foreach($ctx['livewire'] as $lw)
                    <tr><td style="padding:4px 0;color:#6b7280;">Livewire</td><td style="padding:4px 0;">{{ $lw['component'] }} @if(!empty($lw['calls'])) → {{ implode(', ', $lw['calls']) }}() @endif</td></tr>
                @endforeach
            @endif
            @if($console)
                <tr><td style="padding:4px 0;color:#6b7280;">Comando</td><td style="padding:4px 0;"><code>{{ $console['command'] }}</code></td></tr>
            @endif
            @if($user)
                <tr><td style="padding:4px 0;color:#6b7280;">Usuario</td><td style="padding:4px 0;">#{{ $user['id'] }} {{ $user['name'] }} &lt;{{ $user['email'] }}&gt; · restaurante {{ $user['restaurant_id'] ?? '—' }} · sucursal {{ $user['branch_id'] ?? '—' }}</td></tr>
            @endif
            @if(!empty($ctx['log_message']))
                <tr><td style="padding:4px 0;color:#6b7280;">Mensaje log</td><td style="padding:4px 0;">{{ $ctx['log_message'] }}</td></tr>
            @endif
            @if(!empty($ctx['tracker_note']))
                <tr><td style="padding:4px 0;color:#6b7280;">Nota</td><td style="padding:4px 0;color:#b45309;">{{ $ctx['tracker_note'] }}</td></tr>
            @endif
        </table>

        @if($url)
            <p style="margin:20px 0;">
                <a href="{{ $url }}" style="background:#111827;color:#ffffff;text-decoration:none;padding:10px 16px;border-radius:6px;font-size:13px;">Ver detalle en el panel</a>
            </p>
        @endif

        <h3 style="font-size:14px;margin:20px 0 8px;">Stack trace</h3>
        <pre style="white-space:pre-wrap;word-break:break-all;background:#f9fafb;border:1px solid #e5e7eb;padding:12px;border-radius:6px;font-size:11px;line-height:1.5;margin:0;">{{ \Illuminate\Support\Str::limit($p['trace'] ?? '', 8000) }}</pre>
    </div>

    <div style="padding:12px 20px;background:#f9fafb;color:#9ca3af;font-size:11px;border-top:1px solid #e5e7eb;">
        ErrorTracker · Este error se volverá a notificar como máximo cada {{ config('errortracker.notify.cooldown_minutes', 60) }} min.
    </div>
</div>
</body>
</html>
