<?php

namespace App\Support;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Writes one row to activity_logs. Logging must NEVER break the action being logged,
 * so any failure (for example the table not being migrated yet) is swallowed and reported to laravel.log.
 */
class ActivityLogger
{
    public static function log(string $action, string $description, ?Model $model = null, ?int $userId = null): void
    {
        try {
            ActivityLog::create([
                'user_id'     => $userId ?? Auth::id(),
                'action'      => $action,
                'model'       => $model ? class_basename($model) : null,
                'model_id'    => $model?->getKey(),
                'description' => mb_substr($description, 0, 2000),
                'ip'          => app()->runningInConsole() ? null : request()->ip(),
            ]);
        } catch (Throwable $e) {
            Log::warning('Activity log write failed: ' . $e->getMessage());
        }
    }
}
