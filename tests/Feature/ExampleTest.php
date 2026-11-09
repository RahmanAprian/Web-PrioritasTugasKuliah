<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;
    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/login')->assertOk()->assertSee('Masuk ke Prioritas');
        $this->get('/register')->assertOk()->assertSee('Buat akun');
    }

    public function test_registration_and_login_work(): void
    {
        $this->post('/register', ['name'=>'Mahasiswa Demo','email'=>'demo@example.test','password'=>'password123','password_confirmation'=>'password123'])->assertRedirect('/dashboard');
        $this->assertAuthenticated();
        $this->post('/logout')->assertRedirect('/login');
        $this->post('/login', ['email'=>'demo@example.test','password'=>'password123'])->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }
}
