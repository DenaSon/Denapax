<?php

namespace Tests\Feature;

use App\Models\User;
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

    public function test_testing_environment_keeps_http_urls_without_redirecting(): void
    {
        config(['app.env' => 'testing', 'app.url' => 'https://store.example.test']);

        $this->withoutVite();
        $response = $this->get('http://store.example.test/login')->assertOk();

        $this->assertStringContainsString('action="http://store.example.test/login"', $response->getContent());
    }
}
