<?php

namespace App\Traits;

use App\Jobs\ProcessAuditLog;
use Illuminate\Support\Facades\Auth;

trait Auditable
{
    /**
     * Boot the Auditable trait.
     */
    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            static::dispatchAudit('created', $model, null, $model->getAttributes());
        });

        static::updated(function ($model) {
            $changes = $model->getChanges();
            $oldValues = array_intersect_key($model->getOriginal(), $changes);

            // Omitir campos sensibles si existiesen
            unset($oldValues['password'], $changes['password']);

            if (! empty($changes)) {
                static::dispatchAudit('updated', $model, $oldValues, $changes);
            }
        });
    }

    /**
     * Dispatch the audit job asynchronously.
     *
     * @param  array<string, mixed>|null  $old
     * @param  array<string, mixed>|null  $new
     */
    protected static function dispatchAudit(string $event, self $model, ?array $old, ?array $new): void
    {
        ProcessAuditLog::dispatch([
            'event' => $event,
            'user_id' => Auth::id(),
            'model_type' => get_class($model),
            'model_id' => $model->getKey(),
            'old_values' => $old,
            'new_values' => $new,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
