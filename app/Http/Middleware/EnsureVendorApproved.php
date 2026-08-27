<?php

namespace App\Http\Middleware;

use App\Enums\VendorApplicationStatus;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates the /vendor/* route group. Assumes it always runs after `auth`
 * (guests are handled by that middleware first). A user with no
 * VendorProfile at all is sent to apply; a user whose profile isn't yet
 * Approved (Pending/Rejected/Suspended) is sent to the status holding page
 * instead of the dashboard; only an Approved vendor is let through.
 */
class EnsureVendorApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            return redirect()->route('login');
        }

        $vendorProfile = $user->vendorProfile;

        if ($vendorProfile === null) {
            return redirect()->route('vendor.apply');
        }

        if ($vendorProfile->status !== VendorApplicationStatus::Approved) {
            return redirect()->route('vendor.status');
        }

        return $next($request);
    }
}
