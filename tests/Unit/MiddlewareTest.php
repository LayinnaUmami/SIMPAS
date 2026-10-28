<?php

namespace Tests\Unit;

use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureWali;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class MiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function createApplication()
    {
        $app = parent::createApplication();
        $app['db']->connection('sqlite')->getPdo()->sqliteCreateFunction(
            'now',
            fn () => now()->toDateTimeString()
        );

        return $app;
    }

    public function test_middleware_admin_meneruskan_akun_admin(): void
    {
        $response = $this->jalankanMiddleware(new EnsureAdmin, User::ROLE_ADMIN);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('lolos', $response->getContent());
    }

    public function test_middleware_admin_menolak_role_wali_dengan_status_403(): void
    {
        $response = $this->jalankanMiddleware(new EnsureAdmin, User::ROLE_WALI, true);

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame(['message' => 'Anda tidak memiliki akses.'], $response->getData(true));
    }

    public function test_middleware_wali_meneruskan_akun_wali_dan_santri(): void
    {
        foreach ([User::ROLE_WALI, User::ROLE_SANTRI] as $role) {
            $response = $this->jalankanMiddleware(new EnsureWali, $role);

            $this->assertSame(200, $response->getStatusCode());
            $this->assertSame('lolos', $response->getContent());
        }
    }

    public function test_middleware_wali_mengarahkan_admin_kembali_ke_dashboard(): void
    {
        $response = $this->jalankanMiddleware(new EnsureWali, User::ROLE_ADMIN);

        $this->assertTrue($response->isRedirect(route('dashboard')));
    }

    private function jalankanMiddleware(
        EnsureAdmin|EnsureWali $middleware,
        string $role,
        bool $expectsJson = false
    ): Response {
        $user = new User(['role' => $role]);
        $request = Request::create('/middleware-test', 'GET', [], [], [], [
            'HTTP_ACCEPT' => $expectsJson ? 'application/json' : 'text/html',
        ]);
        $request->setUserResolver(fn () => $user);

        return $middleware->handle(
            $request,
            fn () => response('lolos', 200)
        );
    }
}
