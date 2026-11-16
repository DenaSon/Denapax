<?php

namespace Tests\Feature;

use App\Models\User;
use App\Providers\AppServiceProvider;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class RunflareHttpsUrlsTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        URL::forceScheme(null);
        TrustProxies::flushState();
        parent::tearDown();
    }

    public function test_admin_logout_and_livewire_scripts_are_https_on_http_proxy_hop(): void
    {
        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);

        // Request middleware must fix real page URLs without any fixed domain
        // in application code or an early service-provider bootstrap hook.
        config(['app.env' => 'production', 'app.url' => 'https://store.example.test']);

        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $response = $this->actingAs($admin)
            ->get('http://store.example.test/admin')
            ->assertOk();

        $html = $response->getContent();

        $this->assertStringContainsString('action="https://store.example.test/logout"', $html);
        $this->assertMatchesRegularExpression(
            '~<script[^>]+src="https://store\.example\.test/[^"]*livewire\.js[^"]*"~',
            $html,
        );
        $this->assertStringNotContainsString('action="http://store.example.test/logout"', $html);
        $this->assertStringNotContainsString('src="http://store.example.test/', $html);
    }

    public function test_forwarded_https_is_recognized_only_from_configured_proxy(): void
    {
        config([
            'app.env' => 'production',
            'app.url' => 'https://store.example.test',
            'app.trusted_proxies' => '10.0.0.0/8',
        ]);

        (new AppServiceProvider($this->app))->boot();

        Route::get('/_test/forwarded-scheme', fn (Request $request) => response()->json([
            'secure' => $request->isSecure(),
        ]));

        $this->withServerVariables([
            'REMOTE_ADDR' => '10.20.30.40',
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'HTTP_X_FORWARDED_PORT' => '443',
        ])->get('http://store.example.test/_test/forwarded-scheme')
            ->assertOk()->assertJson(['secure' => true]);
    }

    public function test_untrusted_client_cannot_spoof_https_scheme(): void
    {
        config([
            'app.env' => 'testing',
            'app.url' => 'http://store.example.test',
            'app.trusted_proxies' => '10.0.0.0/8',
        ]);

        (new AppServiceProvider($this->app))->boot();

        Route::get('/_test/forwarded-scheme', fn (Request $request) => response()->json([
            'secure' => $request->isSecure(),
        ]));

        $this->withServerVariables([
            'REMOTE_ADDR' => '203.0.113.5',
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'HTTP_X_FORWARDED_PORT' => '443',
        ])->get('http://store.example.test/_test/forwarded-scheme')
            ->assertOk()->assertJson(['secure' => false]);
    }

    public function test_testing_environment_keeps_http_urls_without_redirecting(): void
    {
        config(['app.env' => 'testing', 'app.url' => 'https://store.example.test']);

        $this->withoutVite();
        $response = $this->get('http://store.example.test/login')->assertOk();

        $this->assertStringContainsString('action="http://store.example.test/login"', $response->getContent());
    }
}
