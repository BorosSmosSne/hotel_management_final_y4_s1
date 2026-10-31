<?php

namespace App\Models\Concerns;

use App\Support\ActivityLogger;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;

/**
 * Add `use LogsActivity;` to a model and its created / updated / deleted events are written
 * to activity_logs automatically, whoever triggers them (controllers, services, seeders).
 *
 * A model can override:
 *   activitySubject()      "Booking #12 (Room 101, Jane Doe)"
 *   activityIgnored()      attributes whose changes are not worth logging
 *   activityCreatedNote()  extra detail appended to the "created" entry
 */
trait LogsActivity
{
    public static function bootLogsActivity(): void
    {
        static::created(function ($model) {
            $note = $model->activityCreatedNote();
            ActivityLogger::log('created', $model->activitySubject() . ' created' . ($note ? ': ' . $note : ''), $model);
        });

        static::updated(function ($model) {
            $changes = $model->activityChanges();
            if ($changes === []) {
                return;
            }
            ActivityLogger::log('updated', $model->activitySubject() . ' updated: ' . implode('; ', $changes), $model);
        });

        static::deleted(function ($model) {
            ActivityLogger::log('deleted', $model->activitySubject() . ' deleted', $model);
        });
    }

    protected function activitySubject(): string
    {
        return class_basename($this) . ' #' . $this->getKey();
    }

    /** @return list<string> */
    protected function activityIgnored(): array
    {
        return [];
    }

    protected function activityCreatedNote(): string
    {
        return '';
    }

    /**
     * "field: old → new" for each attribute that really changed. Passwords and tokens never appear.
     *
     * @return list<string>
     */
    protected function activityChanges(): array
    {
        $skip = array_merge(['created_at', 'updated_at', 'remember_token'], $this->activityIgnored());
        $out  = [];

        foreach ($this->getChanges() as $key => $new) {
            if (in_array($key, $skip, true)) {
                continue;
            }
            if ($key === 'password') {
                $out[] = 'password changed';
                continue;
            }
            $out[] = str_replace('_', ' ', $key) . ': '
                . $this->activityValue($this->getOriginal($key)) . ' → ' . $this->activityValue($new);
        }

        return $out;
    }

    private function activityValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }
        if ($value instanceof CarbonInterface) {
            return $value->format('Y-m-d H:i');
        }
        if (is_bool($value)) {
            return $value ? 'yes' : 'no';
        }

        return Str::limit((string) $value, 60);
    }
}
