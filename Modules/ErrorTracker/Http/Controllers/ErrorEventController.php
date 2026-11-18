<?php

namespace Modules\ErrorTracker\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Schema;
use Modules\ErrorTracker\Models\ErrorEvent;

class ErrorEventController extends Controller
{
    public function index(Request $request)
    {
        if (! Schema::hasTable('error_tracker_events')) {
            return view('errortracker::index', ['events' => null, 'status' => 'open', 'search' => '', 'counts' => []]);
        }

        $status = $request->query('status', 'open');
        $search = trim((string) $request->query('q', ''));

        $query = ErrorEvent::query()->orderByDesc('last_seen_at');

        if ($status === 'open') {
            $query->open();
        } elseif ($status === 'resolved') {
            $query->resolved();
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('message', 'like', "%{$search}%")
                    ->orWhere('exception_class', 'like', "%{$search}%")
                    ->orWhere('file', 'like', "%{$search}%");
            });
        }

        return view('errortracker::index', [
            'events' => $query->paginate(25)->withQueryString(),
            'status' => $status,
            'search' => $search,
            'counts' => [
                'open' => ErrorEvent::open()->count(),
                'resolved' => ErrorEvent::resolved()->count(),
                'today' => ErrorEvent::where('last_seen_at', '>=', now()->startOfDay())->count(),
            ],
        ]);
    }

    public function show(ErrorEvent $event)
    {
        return view('errortracker::show', ['event' => $event]);
    }

    public function resolve(ErrorEvent $event)
    {
        $event->update(['resolved_at' => now()]);

        return back();
    }

    public function reopen(ErrorEvent $event)
    {
        $event->update(['resolved_at' => null]);

        return back();
    }

    public function resolveAll()
    {
        ErrorEvent::open()->update(['resolved_at' => now()]);

        return redirect()->route('superadmin.error-tracker.index');
    }

    public function destroy(ErrorEvent $event)
    {
        $event->delete();

        return redirect()->route('superadmin.error-tracker.index');
    }
}
