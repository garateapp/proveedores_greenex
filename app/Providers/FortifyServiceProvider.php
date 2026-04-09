<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Models\ActivityLog;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();
        $this->configureEvents();
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::createUsersUsing(CreateNewUser::class);
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn (Request $request) => Inertia::render('auth/login', [
            'canResetPassword' => Features::enabled(Features::resetPasswords()),
            'canRegister' => Features::enabled(Features::registration()),
            'status' => $request->session()->get('status'),
        ]));

        Fortify::resetPasswordView(fn (Request $request) => Inertia::render('auth/reset-password', [
            'email' => $request->email,
            'token' => $request->route('token'),
        ]));

        Fortify::requestPasswordResetLinkView(fn (Request $request) => Inertia::render('auth/forgot-password', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::verifyEmailView(fn (Request $request) => Inertia::render('auth/verify-email', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::registerView(fn () => Inertia::render('auth/register'));

        Fortify::twoFactorChallengeView(fn () => Inertia::render('auth/two-factor-challenge'));

        Fortify::confirmPasswordView(fn () => Inertia::render('auth/confirm-password'));
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });
    }

    /**
     * Configure event listeners for authentication events.
     */
    private function configureEvents(): void
    {
        // Track successful logins
        Event::listen(function (\Illuminate\Auth\Events\Login $event) {
            $user = $event->user;
            $request = $event->request;
            $userAgent = $request->userAgent() ?? '';

            ActivityLog::create([
                'user_id' => $user->id,
                'event' => 'login',
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'ip_address' => $request->ip(),
                'user_agent' => $userAgent,
                'device_type' => $this->getDeviceType($userAgent),
                'browser' => $this->getBrowser($userAgent),
                'platform' => $this->getPlatform($userAgent),
                'metadata' => [
                    'remember' => $request->has('remember'),
                    'referer' => $request->headers->get('referer'),
                ],
            ]);
        });

        // Track logouts
        Event::listen(function (\Illuminate\Auth\Events\Logout $event) {
            if ($event->user) {
                $user = $event->user;
                $request = request();
                $userAgent = $request->userAgent() ?? '';

                ActivityLog::create([
                    'user_id' => $user->id,
                    'event' => 'logout',
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                    'ip_address' => $request->ip(),
                    'user_agent' => $userAgent,
                    'device_type' => $this->getDeviceType($userAgent),
                    'browser' => $this->getBrowser($userAgent),
                    'platform' => $this->getPlatform($userAgent),
                    'metadata' => [],
                ]);
            }
        });
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
