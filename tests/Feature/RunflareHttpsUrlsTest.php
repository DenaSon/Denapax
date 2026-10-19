<?php

namespace Tests\Feature;

use App\Models\User;
use App\Providers\AppServiceProvider;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class RunflareHttpsUrlsTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        URL::forceScheme(null);
        parent::tearDown();
    }

    public function test_admin_logout_and_livewire_scripts_are_https_even_when_tls_terminates_at_proxy(): void
    {
        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);

        // Mimic production URL generation on the HTTP PHP-FPM hop behind
        // Runflare's public HTTPS ingress. No trusted proxy headers required.
        config(['app.env' => 'production', 'app.url' => 'https://denapax.ir']);
        app(AppServiceProvider::class)->boot();

        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $response = $this->actingAs($admin)
            ->get('http://denapax.ir/admin')
            ->assertOk();

        $html = $response->getContent();

        $this->assertStringContainsString('action="https://denapax.ir/logout"', $html);
        $this->assertMatchesRegularExpression(
            '~<script[^>]+src="https://denapax\.ir/[^"]*livewire\.js[^"]*"~',
            $html,
        );
        $this->assertStringNotContainsString('action="http://denapax.ir/logout"', $html);
        $this->assertStringNotContainsString('src="http://denapax.ir/', $html);
    }

    public function test_non_production_environment_is_not_forced_to_https(): void
    {
        config(['app.env' => 'testing', 'app.url' => 'https://denapax.ir']);
        URL::forceScheme(null);
        app(AppServiceProvider::class)->boot();

        $this->withoutVite();
        $response = $this->get('http://denapax.ir/login')->assertOk();
        $this->assertStringContainsString('action="http://denapax.ir/login"', $response->getContent());
    }
}
