<?php

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class AuditableObserver
{
    /**
     * Handle the Model "created" event.
     */
    public function created(Model $model): void
    {
        if ($this->shouldSkip($model)) {
            return;
        }

        $this->log($model, 'created', null, $this->sanitizeValues($model->getAttributes(), $model));
    }

    /**
     * Handle the Model "updated" event.
     */
    public function updated(Model $model): void
    {
        if ($this->shouldSkip($model)) {
            return;
        }

        $changes = Arr::except($model->getChanges(), ['updated_at']);

        if ($changes === []) {
            return;
        }

        $oldValues = [];
        foreach (array_keys($changes) as $key) {
            $oldValues[$key] = $model->getOriginal($key);
        }

        $this->log(
            $model,
            'updated',
            $this->sanitizeValues($oldValues, $model),
            $this->sanitizeValues($changes, $model),
        );
    }

    /**
     * Handle the Model "deleted" event.
     */
    public function deleted(Model $model): void
    {
        if ($this->shouldSkip($model)) {
            return;
        }

        $this->log($model, 'deleted', $this->sanitizeValues($model->getOriginal(), $model), null);
    }

    /**
     * Handle the Model "restored" event.
     */
    public function restored(Model $model): void
    {
        if ($this->shouldSkip($model)) {
            return;
        }

        $this->log($model, 'restored', null, $this->sanitizeValues($model->getAttributes(), $model));
    }

    /**
     * Handle the Model "force deleted" event.
     */
    public function forceDeleted(Model $model): void
    {
        if ($this->shouldSkip($model)) {
            return;
        }

        $this->log($model, 'force_deleted', $this->sanitizeValues($model->getOriginal(), $model), null);
    }

    private function shouldSkip(Model $model): bool
    {
        if ($model instanceof AuditLog) {
            return true;
        }

        if (! Schema::hasTable('audit_logs')) {
            return true;
        }

        return false;
    }

    private function sanitizeValues(array $values, Model $model): array
    {
        $hidden = $model->getHidden();

        foreach ($hidden as $attribute) {
            unset($values[$attribute]);
        }

        return $values;
    }

    private function log(Model $model, string $event, ?array $oldValues, ?array $newValues): void
    {
        if ($model->getKey() === null) {
            return;
        }

        $request = app()->bound('request') ? request() : null;

        AuditLog::query()->create([
            'auditable_type' => $model::class,
            'auditable_id' => (string) $model->getKey(),
            'event' => $event,
            'user_id' => Auth::id(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'url' => $request?->fullUrl(),
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }
}
