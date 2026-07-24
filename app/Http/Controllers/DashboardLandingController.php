<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Dashboard\DashboardRedirectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardLandingController extends Controller
{
    public function __construct(
        protected DashboardRedirectService $dashboardRedirectService
    ) {
    }

    /**
     * Mengarahkan user ke dashboard utama berdasarkan role.
     *
     * Mapping tujuan ditentukan oleh DashboardRedirectService:
     * - Super Admin / Admin → Management Dashboard
     * - Academic → Academic Dashboard
     * - Marketing → Marketing Dashboard
     * - Sales → Sales Dashboard
     * - Finance → Finance Dashboard
     * - HR → HR Dashboard
     */
    public function __invoke(
        Request $request
    ): RedirectResponse {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(
                401,
                'Silakan login untuk membuka dashboard.'
            );
        }

        $routeName = $this
            ->dashboardRedirectService
            ->resolveRoute($user);

        if (! $routeName) {
            abort(
                403,
                sprintf(
                    'Dashboard belum tersedia untuk role "%s".',
                    filled($user->role)
                        ? (string) $user->role
                        : 'unknown'
                )
            );
        }

        return redirect()->route($routeName);
    }
}
