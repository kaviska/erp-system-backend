<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\Response;
use App\Mail\SecurityAlert;
use App\Models\User;
use Jenssegers\Agent\Agent;

class DeviceSecurityMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only check for authenticated users
        if ($request->user()) {
            $this->checkDeviceSecurity($request, $request->user());
        }

        return $response;
    }

    /**
     * Check device security and send alerts if needed.
     */
    private function checkDeviceSecurity(Request $request, User $user): void
    {
        $agent = new Agent();
        $agent->setUserAgent($request->userAgent());

        $currentBrowser = $agent->browser() . ' ' . $agent->version($agent->browser());
        $currentDevice = $agent->device() ?: ($agent->isDesktop() ? 'Desktop' : 'Unknown Device');
        $currentPlatform = $agent->platform() . ' ' . $agent->version($agent->platform());
        $currentIp = $request->ip();

        if (config('app.debug')) {
            Log::debug("User {$user->id} login attempt", [
                'browser'  => $currentBrowser,
                'device'   => $currentDevice,
                'platform' => $currentPlatform,
                'ip'       => $currentIp,
            ]);
        }

        log::info($this->hasDeviceChanged($user, $currentBrowser, $currentDevice, $currentPlatform));

        // Detect if device/browser/platform changed
        if ($this->hasDeviceChanged($user, $currentBrowser, $currentDevice, $currentPlatform)) {
            $this->sendSecurityAlert($user, $currentBrowser, $currentDevice, $currentPlatform, $currentIp);
        }

        // Save latest login info
        $user->fill([
            'last_login_browser' => $currentBrowser,
            'last_login_device'  => $currentDevice,
            'last_login_platform'=> $currentPlatform,
            'last_login_ip'      => $currentIp,
            'last_login_at'      => now(),
        ])->save();
    }

    /**
     * Check if the device/browser has changed.
     */
    private function hasDeviceChanged(User $user, string $browser, string $device, string $platform): bool
    {
        return (
            $user->last_login_browser !== $browser ||
            $user->last_login_device !== $device ||
            $user->last_login_platform !== $platform
        );
    }

    /**
     * Send security alert email.
     */
    private function sendSecurityAlert(User $user, string $browser, string $device, string $platform, string $ip): void
    {
        try {
            log::info('Sending security alert email to user ' . $user->email);
            Mail::to($user->email)->send(new SecurityAlert(
                $user->first_name . ' ' . $user->last_name,
                $browser,
                $device,
                $platform,
                $ip,
                now()->format('F j, Y \a\t g:i A T'),
                $user->last_login_browser,
                $user->last_login_device,
                $user->last_login_platform,
                $user->last_login_ip
            ));
        } catch (\Exception $e) {
            Log::error('Failed to send security alert email', ['error' => $e->getMessage()]);
        }
    }
}
