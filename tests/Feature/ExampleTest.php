<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testBasicTest()
    {
        $response = $this->get('/');

        $response->assertStatus(200)
            ->assertSee('More integration examples')
            ->assertSee('href="'.url('login-widget').'"', false)
            ->assertSee('href="'.url('add-signature-signed-file').'"', false);
        $this->assertSame(3, substr_count($response->getContent(), '<a class="demo-card'));
    }
}
