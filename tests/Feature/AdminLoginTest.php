<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_login_with_valid_credentials(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'secret-password',
            'is_admin' => true,
        ]);

        $response = $this->post(route('admin.login.submit'), [
            'email' => 'admin@example.com',
            'password' => 'secret-password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticated();
    }

    public function test_wrong_password_shows_login_warning(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'secret-password',
            'is_admin' => true,
        ]);

        $response = $this->from(route('admin.login'))->post(route('admin.login.submit'), [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect(route('admin.login'));
        $response->assertSessionHasErrors(['login']);
        $this->assertGuest();
    }

    public function test_non_admin_user_is_rejected_with_warning(): void
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => 'secret-password',
            'is_admin' => false,
        ]);

        $response = $this->from(route('admin.login'))->post(route('admin.login.submit'), [
            'email' => 'user@example.com',
            'password' => 'secret-password',
        ]);

        $response->assertRedirect(route('admin.login'));
        $response->assertSessionHasErrors(['login']);
        $this->assertGuest();
    }
}
