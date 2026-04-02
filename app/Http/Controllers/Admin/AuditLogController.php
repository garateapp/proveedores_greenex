<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->input('search', ''));
        $event = trim((string) $request->input('event', ''));

        $auditLogs = AuditLog::query()
            ->with('user:id,name,email')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $nestedQuery) use ($search): void {
                    $nestedQuery
                        ->where('auditable_type', 'like', "%{$search}%")
                        ->orWhere('auditable_id', 'like', "%{$search}%")
                        ->orWhere('url', 'like', "%{$search}%")
                        ->orWhereHas('user', function (Builder $userQuery) use ($search): void {
                            $userQuery
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->when($event !== '', function (Builder $query) use ($event): void {
                $query->where('event', $event);
            })
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/audit-logs/index', [
            'auditLogs' => $auditLogs->through(function (AuditLog $auditLog): array {
                return [
                    'id' => $auditLog->id,
                    'event' => $auditLog->event,
                    'event_label' => $this->resolveEventLabel($auditLog->event),
                    'auditable_type' => $auditLog->auditable_type,
                    'auditable_model' => $this->resolveAuditableModel($auditLog->auditable_type),
                    'auditable_id' => $auditLog->auditable_id,
                    'user' => $auditLog->user ? [
                        'id' => $auditLog->user->id,
                        'name' => $auditLog->user->name,
                        'email' => $auditLog->user->email,
                    ] : null,
                    'changed_fields' => $this->resolveChangedFields($auditLog),
                    'old_values' => $auditLog->old_values ?? [],
                    'new_values' => $auditLog->new_values ?? [],
                    'url' => $auditLog->url,
                    'ip_address' => $auditLog->ip_address,
                    'user_agent' => $auditLog->user_agent,
                    'created_at' => $auditLog->created_at?->format('d/m/Y H:i:s'),
                ];
            }),
            'filters' => [
                'search' => $search,
                'event' => $event,
            ],
            'events' => [
                ['value' => 'created', 'label' => 'Creación'],
                ['value' => 'updated', 'label' => 'Actualización'],
                ['value' => 'deleted', 'label' => 'Eliminación'],
                ['value' => 'restored', 'label' => 'Restauración'],
                ['value' => 'force_deleted', 'label' => 'Eliminación definitiva'],
            ],
        ]);
    }

    private function resolveEventLabel(string $event): string
    {
        return match ($event) {
            'created' => 'Creación',
            'updated' => 'Actualización',
            'deleted' => 'Eliminación',
            'restored' => 'Restauración',
            'force_deleted' => 'Eliminación definitiva',
            default => Str::of($event)
                ->replace('_', ' ')
                ->headline()
                ->toString(),
        };
    }

    private function resolveAuditableModel(string $auditableType): string
    {
        return Str::of(class_basename($auditableType))
            ->headline()
            ->toString();
    }

    /**
     * @return array<int, string>
     */
    private function resolveChangedFields(AuditLog $auditLog): array
    {
        $oldFields = array_keys($auditLog->old_values ?? []);
        $newFields = array_keys($auditLog->new_values ?? []);

        return collect(array_merge($oldFields, $newFields))
            ->filter(fn (string $field): bool => $field !== '')
            ->unique()
            ->values()
            ->all();
    }
}
