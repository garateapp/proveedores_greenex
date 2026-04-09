<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TrackUserActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only track authenticated users and successful responses
        if (Auth::check() && $response->getStatusCode() === 200) {
            $user = Auth::user();
            $userAgent = $request->userAgent() ?? '';

            ActivityLog::create([
                'user_id' => $user->id,
                'event' => 'page_view',
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'ip_address' => $request->ip(),
                'user_agent' => $userAgent,
                'device_type' => $this->getDeviceType($userAgent),
                'browser' => $this->getBrowser($userAgent),
                'platform' => $this->getPlatform($userAgent),
                'metadata' => [
                    'route' => $request->route()?->getName(),
                    'route_action' => $request->route()?->getActionName(),
                    'query_params' => $request->query(),
                    'referer' => $request->headers->get('referer'),
                ],
            ]);
        }

        return $response;
    }

    /**
     * Determine device type from user agent.
     */
    private function getDeviceType(string $userAgent): string
    {
        if (preg_match('/Mobile|Android|iPhone|iPad|iPod/i', $userAgent)) {
            return 'mobile';
        }

        if (preg_match('/Tablet|iPad/i', $userAgent)) {
            return 'tablet';
        }

        return 'desktop';
    }

    /**
     * Extract browser name from user agent.
     */
    private function getBrowser(string $userAgent): string
    {
        $browsers = [
            'Chrome' => '/Chrome/i',
            'Firefox' => '/Firefox/i',
            'Safari' => '/Safari/i',
            'Edge' => '/Edg/i',
            'Opera' => '/Opera|OPR/i',
        ];

        foreach ($browsers as $name => $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return $name;
            }
        }

        return 'Unknown';
    }

    /**
     * Extract platform/OS from user agent.
     */
    private function getPlatform(string $userAgent): string
    {
        $platforms = [
            'Windows' => '/Windows NT/i',
            'Mac OS' => '/Macintosh/i',
            'Linux' => '/Linux/i',
            'Android' => '/Android/i',
            'iOS' => '/iPhone|iPad|iPod/i',
        ];

        foreach ($platforms as $name => $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return $name;
            }
        }

        return 'Unknown';
    }
}
