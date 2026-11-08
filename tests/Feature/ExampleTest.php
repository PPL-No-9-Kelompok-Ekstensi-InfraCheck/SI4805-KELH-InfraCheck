<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_redirects_to_dashboard(): void
    {
        $response = $this->get('/');

        // Halaman utama mengalihkan ke dashboard (kemudian ke login bila belum masuk)
        $response->assertStatus(302);
    }
}
