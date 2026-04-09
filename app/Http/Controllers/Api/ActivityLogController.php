<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivityLogController extends Controller
{
    /**
     * Get activity logs for the authenticated user or all logs (for admins).
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $isAdmin = $user->isAdmin();

        $query = ActivityLog::with('user:id,name,email');

        // Non-admin users can only see their own activity
        if (! $isAdmin) {
            $query->forUser($user);
        }

        // Filter by user_id (admin only)
        if ($isAdmin && $request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by event type
        if ($request->filled('event')) {
            $query->byEvent($request->event);
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->dateRange($request->start_date, $request->end_date ?? null);
        }

        // Filter by device type
        if ($request->filled('device_type')) {
            $query->where('device_type', $request->device_type);
        }

        // Search in URL and metadata
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('url', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $activityLogs = $query->orderByDesc('created_at')
            ->paginate($request->get('per_page', 20))
            ->through(function (ActivityLog $activityLog) {
                return [
                    'id' => $activityLog->id,
                    'user_id' => $activityLog->user_id,
                    'user' => $activityLog->user ? [
                        'id' => $activityLog->user->id,
                        'name' => $activityLog->user->name,
                        'email' => $activityLog->user->email,
                    ] : null,
                    'event' => $activityLog->event,
                    'event_label' => $this->getEventLabel($activityLog->event),
                    'url' => $activityLog->url,
                    'method' => $activityLog->method,
                    'ip_address' => $activityLog->ip_address,
                    'device_type' => $activityLog->device_type,
                    'browser' => $activityLog->browser,
                    'platform' => $activityLog->platform,
                    'metadata' => $activityLog->metadata ?? [],
                    'created_at' => $activityLog->created_at?->format('Y-m-d H:i:s'),
                ];
            });

        return response()->json($activityLogs);
    }

    /**
     * Get a specific activity log entry.
     */
    public function show(ActivityLog $activityLog): JsonResponse
    {
        $user = Auth::user();

        // Non-admin users can only view their own activity logs
        if (! $user->isAdmin() && $activityLog->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $activityLog->load('user:id,name,email');

        return response()->json([
            'id' => $activityLog->id,
            'user_id' => $activityLog->user_id,
            'user' => $activityLog->user ? [
                'id' => $activityLog->user->id,
                'name' => $activityLog->user->name,
                'email' => $activityLog->user->email,
            ] : null,
            'event' => $activityLog->event,
            'event_label' => $this->getEventLabel($activityLog->event),
            'url' => $activityLog->url,
            'method' => $activityLog->method,
            'ip_address' => $activityLog->ip_address,
            'user_agent' => $activityLog->user_agent,
            'device_type' => $activityLog->device_type,
            'browser' => $activityLog->browser,
            'platform' => $activityLog->platform,
            'metadata' => $activityLog->metadata ?? [],
            'created_at' => $activityLog->created_at?->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get activity summary/statistics.
     */
    public function summary(Request $request): JsonResponse
    {
        $user = Auth::user();
        $isAdmin = $user->isAdmin();

        $query = ActivityLog::query();

        // Non-admin users can only see their own summary
        if (! $isAdmin) {
            $query->forUser($user);
        }

        // Filter by user_id (admin only)
        if ($isAdmin && $request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->dateRange($request->start_date, $request->end_date ?? now());
        }

        // Get event type counts
        $eventCounts = (clone $query)->selectRaw('event, COUNT(*) as count')
            ->groupBy('event')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->event => $item->count];
            });

        // Get device type breakdown
        $deviceBreakdown = (clone $query)->selectRaw('device_type, COUNT(*) as count')
            ->whereNotNull('device_type')
            ->groupBy('device_type')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->device_type => $item->count];
            });

        // Get browser breakdown
        $browserBreakdown = (clone $query)->selectRaw('browser, COUNT(*) as count')
            ->whereNotNull('browser')
            ->groupBy('browser')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->browser => $item->count];
            });

        // Get platform breakdown
        $platformBreakdown = (clone $query)->selectRaw('platform, COUNT(*) as count')
            ->whereNotNull('platform')
            ->groupBy('platform')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->platform => $item->count];
            });

        // Get daily activity for the last 30 days
        $dailyActivity = (clone $query)->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->date => $item->count];
            });

        // Get most active users (admin only)
        $mostActiveUsers = [];
        if ($isAdmin) {
            $mostActiveUsers = ActivityLog::selectRaw('user_id, COUNT(*) as count')
                ->with('user:id,name,email')
                ->when($request->filled('start_date'), function ($q) use ($request) {
                    $q->dateRange($request->start_date, $request->end_date ?? now());
                })
                ->groupBy('user_id')
                ->orderByDesc('count')
                ->limit(10)
                ->get()
                ->map(function ($item) {
                    return [
                        'user' => $item->user ? [
                            'id' => $item->user->id,
                            'name' => $item->user->name,
                            'email' => $item->user->email,
                        ] : null,
                        'activity_count' => $item->count,
                    ];
                });
        }

        return response()->json([
            'total_activities' => (clone $query)->count(),
            'event_counts' => $eventCounts,
            'device_breakdown' => $deviceBreakdown,
            'browser_breakdown' => $browserBreakdown,
            'platform_breakdown' => $platformBreakdown,
            'daily_activity' => $dailyActivity,
            'most_active_users' => $mostActiveUsers,
            'period' => [
                'start_date' => $request->start_date ?? now()->subDays(30)->format('Y-m-d'),
                'end_date' => $request->end_date ?? now()->format('Y-m-d'),
            ],
        ]);
    }

    /**
     * Get user's recent navigation history.
     */
    public function navigationHistory(Request $request): JsonResponse
    {
        $user = Auth::user();
        $targetUser = $user;

        // Admin can view other users' navigation history
        if ($user->isAdmin() && $request->filled('user_id')) {
            $targetUser = User::findOrFail($request->user_id);
        }

        $limit = $request->get('limit', 50);

        $activities = ActivityLog::forUser($targetUser)
            ->byEvent('page_view')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(function (ActivityLog $activity) {
                return [
                    'url' => $activity->url,
                    'method' => $activity->method,
                    'route' => $activity->metadata['route'] ?? null,
                    'ip_address' => $activity->ip_address,
                    'device_type' => $activity->device_type,
                    'browser' => $activity->browser,
                    'platform' => $activity->platform,
                    'timestamp' => $activity->created_at?->format('Y-m-d H:i:s'),
                ];
            });

        return response()->json([
            'user' => [
                'id' => $targetUser->id,
                'name' => $targetUser->name,
                'email' => $targetUser->email,
            ],
            'navigation_history' => $activities,
            'total_records' => $activities->count(),
        ]);
    }

    /**
     * Get human-readable event labels.
     */
    private function getEventLabel(string $event): string
    {
        $labels = [
            'login' => 'Inicio de sesión',
            'logout' => 'Cierre de sesión',
            'page_view' => 'Navegación',
            'password_change' => 'Cambio de contraseña',
            'profile_update' => 'Actualización de perfil',
            'document_upload' => 'Carga de documento',
            'document_approval' => 'Aprobación de documento',
            'document_rejection' => 'Rechazo de documento',
            'worker_create' => 'Creación de trabajador',
            'worker_update' => 'Actualización de trabajador',
            'attendance_mark' => 'Registro de asistencia',
        ];

        return $labels[$event] ?? ucfirst(str_replace('_', ' ', $event));
    }
}
