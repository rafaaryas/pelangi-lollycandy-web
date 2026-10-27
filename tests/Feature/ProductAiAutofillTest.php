<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProductAiAutofillTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function photo(): UploadedFile
    {
        return UploadedFile::fake()->image('product.jpg', 80, 80);
    }

    private function category(bool $active = true): Category
    {
        return Category::create(['name' => 'Lollipop', 'slug' => 'lollipop', 'is_active' => $active]);
    }

    private function fakeResponse(?int $categoryId, bool $review = false, bool $relevant = true): array
    {
        return ['candidates' => [['content' => ['parts' => [['text' => json_encode([
            'name' => 'Permen Lolipop Warna-Warni',
            'category_id' => $categoryId,
            'description' => 'Permen lolipop dengan warna cerah.',
            'is_relevant' => $relevant,
            'needs_review' => $review,
            'reason' => $review ? 'Foto kurang jelas.' : 'Bentuk lolipop terlihat.',
        ])]]]]]];
    }

    public function test_admin_authentication_is_required(): void
    {
        $this->postJson(route('admin.products.ai-autofill'), ['image' => $this->photo()])->assertUnauthorized();
    }

    public function test_invalid_image_is_rejected_before_gemini(): void
    {
        Http::fake();
        $this->actingAs($this->admin())->postJson(route('admin.products.ai-autofill'), [
            'image' => UploadedFile::fake()->create('product.txt', 1, 'text/plain'),
        ])->assertUnprocessable()->assertJsonValidationErrors('image');
        Http::assertNothingSent();
    }

    public function test_missing_key_returns_setup_message(): void
    {
        $this->category();
        config()->set('services.gemini.api_key', null);
        Http::fake();
        $this->actingAs($this->admin())->postJson(route('admin.products.ai-autofill'), ['image' => $this->photo()])
            ->assertStatus(503)->assertJsonPath('message', 'GEMINI_API_KEY belum dikonfigurasi.');
        Http::assertNothingSent();
    }

    public function test_valid_response_uses_active_database_category_without_saving_product(): void
    {
        $category = $this->category();
        config()->set('services.gemini.api_key', 'test-key');
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response($this->fakeResponse($category->id))]);
        $this->actingAs($this->admin())->postJson(route('admin.products.ai-autofill'), ['image' => $this->photo()])
            ->assertOk()->assertJsonPath('category_id', $category->id)->assertJsonPath('needs_review', false);
        $this->assertSame(0, Product::count());
        Http::assertSent(fn ($request) => $request->hasHeader('x-goog-api-key', 'test-key')
            && $request['generationConfig']['responseMimeType'] === 'application/json'
            && $request['contents'][0]['parts'][1]['inlineData']['mimeType'] === 'image/jpeg');
    }

    public function test_unknown_category_is_removed_and_flagged_for_review(): void
    {
        $this->category();
        config()->set('services.gemini.api_key', 'test-key');
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response($this->fakeResponse(999))]);
        $this->actingAs($this->admin())->postJson(route('admin.products.ai-autofill'), ['image' => $this->photo()])
            ->assertOk()->assertJsonPath('category_id', null)->assertJsonPath('needs_review', true);
    }

    public function test_inactive_category_id_is_not_accepted(): void
    {
        $this->category();
        $inactive = Category::create(['name' => 'Gulali', 'slug' => 'gulali', 'is_active' => false]);
        config()->set('services.gemini.api_key', 'test-key');
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response($this->fakeResponse($inactive->id))]);
        $this->actingAs($this->admin())->postJson(route('admin.products.ai-autofill'), ['image' => $this->photo()])
            ->assertOk()->assertJsonPath('category_id', null)->assertJsonPath('needs_review', true);
    }

    public function test_no_active_categories_prevents_api_call(): void
    {
        $this->category(false);
        Http::fake();
        $this->actingAs($this->admin())->postJson(route('admin.products.ai-autofill'), ['image' => $this->photo()])
            ->assertUnprocessable();
        Http::assertNothingSent();
    }

    public function test_gemini_rate_limit_has_clear_error(): void
    {
        $this->category();
        config()->set('services.gemini.api_key', 'test-key');
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([], 429)]);
        $this->actingAs($this->admin())->postJson(route('admin.products.ai-autofill'), ['image' => $this->photo()])->assertStatus(429);
    }

    public function test_unavailable_model_has_clear_error(): void
    {
        $this->category();
        config()->set('services.gemini.api_key', 'test-key');
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([
            'error' => ['message' => 'This model is no longer available to new users.'],
        ], 404)]);
        $this->actingAs($this->admin())->postJson(route('admin.products.ai-autofill'), ['image' => $this->photo()])
            ->assertStatus(503)
            ->assertJsonPath('message', fn (string $message) => str_contains($message, 'no longer available to new users'));
    }

    public function test_timeout_returns_retryable_error(): void
    {
        $this->category();
        config()->set('services.gemini.api_key', 'test-key');
        Http::fake(fn () => throw new ConnectionException('timeout'));
        $this->actingAs($this->admin())->postJson(route('admin.products.ai-autofill'), ['image' => $this->photo()])->assertStatus(503);
    }

    public function test_invalid_json_returns_retryable_error(): void
    {
        $this->category();
        config()->set('services.gemini.api_key', 'test-key');
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['candidates' => [['content' => ['parts' => [['text' => 'bad']]]]]])]);
        $this->actingAs($this->admin())->postJson(route('admin.products.ai-autofill'), ['image' => $this->photo()])->assertStatus(502);
    }

    public function test_ambiguous_response_preserves_review_flag(): void
    {
        $category = $this->category();
        config()->set('services.gemini.api_key', 'test-key');
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response($this->fakeResponse($category->id, true))]);
        $this->actingAs($this->admin())->postJson(route('admin.products.ai-autofill'), ['image' => $this->photo()])
            ->assertOk()->assertJsonPath('needs_review', true);
    }

    public function test_irrelevant_photo_does_not_autofill_product_fields(): void
    {
        $category = $this->category();
        config()->set('services.gemini.api_key', 'test-key');
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response($this->fakeResponse($category->id, false, false))]);
        $this->actingAs($this->admin())->postJson(route('admin.products.ai-autofill'), ['image' => $this->photo()])
            ->assertStatus(422)->assertJsonPath('message', fn (string $message) => str_contains($message, 'tidak menunjukkan produk permen'));
        $this->assertSame(0, Product::count());
    }
}
