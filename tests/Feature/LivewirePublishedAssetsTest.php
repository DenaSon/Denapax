<?php

namespace Tests\Feature;

use Tests\TestCase;

class LivewirePublishedAssetsTest extends TestCase
{
    public function test_livewire_javascript_is_published_as_a_static_asset(): void
    {
        $manifestPath = public_path('vendor/livewire/manifest.json');
        $scriptPath = public_path('vendor/livewire/livewire.min.js');

        $this->assertFileExists($manifestPath);
        $this->assertFileExists($scriptPath);

        $manifest = json_decode(file_get_contents($manifestPath), true);

        $this->assertIsArray($manifest);
        $this->assertArrayHasKey('/livewire.js', $manifest);
        $this->assertNotEmpty($manifest['/livewire.js']);
    }
}
