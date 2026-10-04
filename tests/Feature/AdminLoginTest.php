<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_uses_brand_assets_and_accessible_password_toggle(): void
    {
        $this->get(route('admin.login'))
            ->assertOk()
            ->assertSee('Selamat datang kembali')
            ->assertSee('images/pelangi-logo.png')
            ->assertSee('images/ceria.jpg')
            ->assertDontSee('RUANG KERJA ADMIN')
            ->assertSee('autocomplete="email"', false)
            ->assertSee('autocomplete="current-password"', false)
            ->assertSee('Tampilkan password');
    }

    public function test_login_validation_uses_clear_indonesian_messages(): void
    {
        $this->from(route('admin.login'))
            ->post(route('admin.login.submit'), ['email' => '', 'password' => ''])
            ->assertRedirect(route('admin.login'))
            ->assertSessionHasErrors([
                'email' => 'Email wajib diisi.',
                'password' => 'Password wajib diisi.',
            ]);

        $this->from(route('admin.login'))
            ->post(route('admin.login.submit'), ['email' => 'alamat-salah', 'password' => 'pass'])
            ->assertSessionHasErrors(['email' => 'Masukkan alamat email yang valid.']);
    }

    public function test_guest_visiting_admin_dashboard_is_redirected_to_admin_login(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_guest_visiting_protected_admin_route_is_redirected_to_admin_login(): void
    {
        $this->get(route('admin.products.index'))
            ->assertRedirect(route('admin.login'));
    }

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
