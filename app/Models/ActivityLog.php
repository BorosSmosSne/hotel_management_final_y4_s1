<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Append-only audit trail. Rows are never edited from the app. */
class ActivityLog extends Model
{
    public const UPDATED_AT = null;   // table has created_at only

    public const ACTIONS = [
        'created'      => 'Created',
        'updated'      => 'Updated',
        'deleted'      => 'Deleted',
        'login'        => 'Login',
        'logout'       => 'Logout',
        'login_failed' => 'Failed login',
    ];

    protected $fillable = ['user_id', 'action', 'model', 'model_id', 'description', 'ip'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actionLabel(): string
    {
        return self::ACTIONS[$this->action] ?? ucfirst($this->action);
    }
}
