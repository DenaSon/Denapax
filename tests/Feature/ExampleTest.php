<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_public_homepage_is_available(): void
    {
        $this->withoutVite();

        $this->get('/')->assertOk()->assertSee('از دل دنا');
    }
}
