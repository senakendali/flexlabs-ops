<?php

namespace App\Services\Dashboard;

use App\Models\User;
use Illuminate\Support\Facades\Route;
use RuntimeException;

class DashboardRedirectService
{
    /**
     * Dashboard utama berdasarkan role resmi FlexOps.
     *
     * Role dipakai sebagai sumber utama agar Super Admin dan Admin
     * tidak salah diarahkan ke dashboard divisi hanya karena memiliki
     * wildcard permission "*".
     *
     * @var array<string, string>
     */
    private const ROLE_DASHBOARD_ROUTES = [
        'super_admin' => 'management.dashboard',
        'admin' => 'management.dashboard',

        'academic' => 'academic.dashboard',
        'marketing' => 'marketing.dashboard',
        'sales' => 'sales.dashboard',
        'finance' => 'finance.dashboard',
        'hr' => 'hr.dashboard',
    ];

    /**
     * Fallback berdasarkan permission.
     *
     * Digunakan ketika role user tidak memiliki mapping langsung,
     * tetapi user tetap mempunyai akses ke salah satu dashboard divisi.
     *
     * Management Dashboard tidak dimasukkan ke fallback permission
     * karena akses Super Admin dan Admin sudah ditentukan dari role.
     *
     * @var array<string, string>
     */
    private const PERMISSION_DASHBOARD_ROUTES = [
        'academic.dashboard.view' => 'academic.dashboard',
        'marketing.dashboard.view' => 'marketing.dashboard',
        'sales.dashboard.view' => 'sales.dashboard',
        'finance.dashboard.view' => 'finance.dashboard',
        'hr.dashboard.view' => 'hr.dashboard',
    ];

    /**
     * Menentukan route dashboard utama untuk user.
     *
     * Return null ketika:
     * - role belum mempunyai dashboard khusus;
     * - route dashboard belum terdaftar;
     * - user tidak mempunyai permission dashboard divisi.
     */
    public function resolveRoute(User $user): ?string
    {
        $role = $this->normalizeRole($user->role);

        if (
            array_key_exists(
                $role,
                self::ROLE_DASHBOARD_ROUTES
            )
        ) {
            $roleRoute = self::ROLE_DASHBOARD_ROUTES[
                $role
            ];

            return Route::has($roleRoute)
                ? $roleRoute
                : null;
        }

        foreach (
            self::PERMISSION_DASHBOARD_ROUTES
            as $permission => $routeName
        ) {
            if (
                ! Route::has($routeName)
                || ! $user->canAccess($permission)
            ) {
                continue;
            }

            return $routeName;
        }

        return null;
    }

    /**
     * Menentukan route dashboard dan melempar exception
     * apabila tidak ada dashboard yang dapat digunakan.
     */
    public function resolveRouteOrFail(User $user): string
    {
        $routeName = $this->resolveRoute($user);

        if ($routeName) {
            return $routeName;
        }

        throw new RuntimeException(
            sprintf(
                'Dashboard route belum tersedia untuk user ID %s dengan role "%s".',
                (string) $user->getKey(),
                $this->normalizeRole($user->role) ?: 'unknown'
            )
        );
    }

    /**
     * Mengecek apakah user mempunyai dashboard tujuan.
     */
    public function hasDashboard(User $user): bool
    {
        return $this->resolveRoute($user) !== null;
    }

    /**
     * Mendapatkan route dashboard berdasarkan role tanpa
     * melakukan pengecekan permission fallback.
     */
    public function routeForRole(?string $role): ?string
    {
        $routeName = self::ROLE_DASHBOARD_ROUTES[
            $this->normalizeRole($role)
        ] ?? null;

        if (
            ! $routeName
            || ! Route::has($routeName)
        ) {
            return null;
        }

        return $routeName;
    }

    /**
     * Daftar mapping dashboard untuk kebutuhan debug atau testing.
     *
     * @return array<string, string>
     */
    public function roleMappings(): array
    {
        return self::ROLE_DASHBOARD_ROUTES;
    }

    /**
     * Normalisasi role agar mapping tetap aman terhadap
     * spasi dan perbedaan huruf besar/kecil.
     */
    private function normalizeRole(?string $role): string
    {
        return strtolower(
            trim((string) $role)
        );
    }
}
