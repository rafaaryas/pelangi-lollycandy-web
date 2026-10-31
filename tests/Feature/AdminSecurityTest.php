<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->freezeTime();
    }

    public function test_failed_login_is_limited_and_recovers_after_cooldown(): void
    {
        User::factory()->create(['email' => 'admin@example.test', 'password' => 'correct-password', 'is_admin' => true]);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/admin/login', ['email' => 'admin@example.test', 'password' => 'wrong-password'])
                ->assertSessionHasErrors('login');
        }

        $this->post('/admin/login', ['email' => 'ADMIN@example.test', 'password' => 'correct-password'])
            ->assertSessionHasErrors(['login' => 'Terlalu banyak percobaan login. Coba lagi dalam 60 detik.']);
        $this->assertGuest();

        $this->travel(61)->seconds();
        $this->post('/admin/login', ['email' => 'admin@example.test', 'password' => 'correct-password'])
            ->assertRedirect('/admin');
        $this->assertAuthenticated();
    }

    public function test_successful_admin_login_clears_account_attempts_without_locking_another_account(): void
    {
        User::factory()->create(['email' => 'admin@example.test', 'password' => 'correct-password', 'is_admin' => true]);
        User::factory()->create(['email' => 'second@example.test', 'password' => 'second-password', 'is_admin' => true]);
        for ($attempt = 0; $attempt < 4; $attempt++) {
            $this->post('/admin/login', ['email' => 'admin@example.test', 'password' => 'wrong']);
        }
        $this->post('/admin/login', ['email' => 'admin@example.test', 'password' => 'correct-password'])->assertRedirect('/admin');
        $this->post('/admin/logout')->assertRedirect('/admin/login');
        $this->assertGuest();
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/admin/login', ['email' => 'admin@example.test', 'password' => 'wrong'])
                ->assertSessionHasErrors(['login' => 'Email atau password salah. Silakan cek kembali data login Anda.']);
        }
        $this->post('/admin/login', ['email' => 'second@example.test', 'password' => 'second-password'])
            ->assertRedirect('/admin');
    }

    public function test_ip_limit_prevents_rotating_account_names(): void
    {
        for ($attempt = 0; $attempt < 30; $attempt++) {
            $this->post('/admin/login', ['email' => "unknown{$attempt}@example.test", 'password' => 'wrong']);
        }
        $this->post('/admin/login', ['email' => 'another@example.test', 'password' => 'wrong'])
            ->assertSessionHasErrors(['login' => 'Terlalu banyak percobaan login. Coba lagi dalam 60 detik.']);
        $this->assertGuest();
    }

    public function test_guests_and_non_admins_cannot_access_exports_or_mutations(): void
    {
        $this->get('/admin/export/products?format=csv')->assertRedirect('/admin/login');
        $this->post('/admin/products', [])->assertRedirect('/admin/login');
        $this->post('/admin/stock/adjust', [])->assertRedirect('/admin/login');

        $this->actingAs(User::factory()->create(['is_admin' => false]));
        $this->get('/admin/export/products?format=csv')->assertForbidden();
        $this->post('/admin/products', [])->assertForbidden();
        $this->post('/admin/stock/adjust', [])->assertForbidden();
        $this->assertDatabaseCount('products', 0);
    }

    public function test_seeding_never_provisions_or_resets_an_admin_and_preserves_catalog_edits(): void
    {
        $user = User::factory()->create(['password' => 'existing-secret', 'is_admin' => false]);
        $hash = $user->password;
        $this->seed(DatabaseSeeder::class);
        $product = Product::firstOrFail();
        $product->update(['price_from' => 12345, 'description' => 'Deskripsi yang telah diedit.']);
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('users', 1);
        $this->assertSame($hash, $user->fresh()->password);
        $this->assertFalse($user->fresh()->is_admin);
        $this->assertSame('12345.00', $product->fresh()->price_from);
        $this->assertSame('Deskripsi yang telah diedit.', $product->fresh()->description);
    }

    public function test_demo_seeder_does_not_write_in_production(): void
    {
        $this->app->instance('env', 'production');
        (new DatabaseSeeder)->run();
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('categories', 0);
        $this->assertDatabaseCount('marketplace_links', 0);
    }

    public function test_explicit_admin_provisioning_hashes_password_and_refuses_existing_accounts(): void
    {
        $this->artisan('admin:create')
            ->expectsQuestion('Nama admin', 'Pemilik')
            ->expectsQuestion('Email admin', 'owner@example.test')
            ->expectsQuestion('Password baru (minimal 12 karakter)', 'a-long-unique-password')
            ->expectsQuestion('Ulangi password', 'a-long-unique-password')
            ->assertSuccessful();
        $user = User::where('email', 'owner@example.test')->firstOrFail();
        $hash = $user->password;
        $this->assertTrue($user->is_admin);
        $this->assertTrue(Hash::check('a-long-unique-password', $hash));

        $this->artisan('admin:create')
            ->expectsQuestion('Nama admin', 'Nama baru')
            ->expectsQuestion('Email admin', 'owner@example.test')
            ->expectsQuestion('Password baru (minimal 12 karakter)', 'another-long-password')
            ->expectsQuestion('Ulangi password', 'another-long-password')
            ->assertFailed();
        $this->assertSame($hash, $user->fresh()->password);
        $this->assertSame('Pemilik', $user->fresh()->name);
    }
}
