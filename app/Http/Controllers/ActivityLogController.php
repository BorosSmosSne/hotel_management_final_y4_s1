<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Throwable;

/** Read-only activity log (Admin only, via role:admin on the route). There is no edit or delete. */
class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $from   = $this->parseDate($request->query('from'));
        $to     = $this->parseDate($request->query('to'));
        $search = trim((string) $request->query('search', ''));
        $action = array_key_exists((string) $request->query('action'), ActivityLog::ACTIONS) ? $request->query('action') : null;
        $userId = ctype_digit((string) $request->query('user_id', '')) ? (int) $request->query('user_id') : null;

        $logs = ActivityLog::query()
            ->with('user')
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->when($action, fn ($q) => $q->where('action', $action))
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from->copy()->startOfDay()))
            ->when($to, fn ($q) => $q->where('created_at', '<=', $to->copy()->endOfDay()))
            ->when($search !== '', fn ($q) => $q->where('description', 'like', '%' . $search . '%'))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('activity-log.index', [
            'logs'    => $logs,
            'users'   => User::orderBy('name')->get(['id', 'name']),
            'actions' => ActivityLog::ACTIONS,
        ]);
    }

    private function parseDate(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }
        try {
            return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
        } catch (Throwable) {
            return null;
        }
    }
}
