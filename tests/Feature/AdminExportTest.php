<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Database\Seeders\DemoBusinessDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_requires_admin_authentication(): void
    {
        $this->get(route('admin.export', ['module' => 'products', 'format' => 'csv']))->assertRedirect(route('admin.login'));
        $this->actingAs(User::factory()->create(['is_admin' => false]))
            ->get(route('admin.export', ['module' => 'products', 'format' => 'csv']))->assertForbidden();
    }

    public function test_csv_exports_all_filtered_rows_and_escapes_formulas(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $category = Category::create(['name' => 'Lollipop', 'slug' => 'lollipop', 'is_active' => true]);
        Product::create(['name' => '=HYPERLINK("bad")', 'slug' => 'formula', 'category_id' => $category->id, 'description' => 'Permen', 'price_from' => 12000, 'is_active' => true]);
        Product::create(['name' => 'Produk Nonaktif', 'slug' => 'inactive', 'category_id' => $category->id, 'description' => 'Permen', 'price_from' => 8000, 'is_active' => false]);

        $content = $this->get(route('admin.export', ['module' => 'products', 'format' => 'csv', 'status' => 'active']))
            ->assertOk()->assertDownload()->streamedContent();
        $this->assertStringContainsString("'=HYPERLINK", $content);
        $this->assertStringNotContainsString('Produk Nonaktif', $content);
        $this->assertStringContainsString('12000', $content);
    }

    public function test_every_operational_module_exports_xlsx_and_pdf(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 3)->startOfDay());
        $category = Category::create(['name' => 'Lollipop', 'slug' => 'lollipop', 'is_active' => true]);
        Product::create(['name' => 'Lolly Uji', 'slug' => 'lolly-uji', 'category_id' => $category->id, 'description' => 'Permen', 'price_from' => 12000, 'is_active' => true]);
        $this->seed(DemoBusinessDataSeeder::class);
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $material = \App\Models\RawMaterial::firstOrFail();
        $modules = [
            'dashboard', 'categories', 'products', 'raw-materials', 'suppliers', 'customers',
            'purchases', 'productions', 'sales', 'stock', 'reports', 'stock-history', 'marketplace-links',
        ];
        foreach ($modules as $module) {
            $parameters = ['module' => $module, 'format' => 'xlsx'];
            if ($module === 'stock-history') $parameters += ['type' => 'materials', 'id' => $material->id];
            $xlsx = $this->get(route('admin.export', $parameters))->assertOk()->assertDownload()->getContent();
            $this->assertStringStartsWith("PK\x03\x04", $xlsx, $module);
            $parameters['format'] = 'pdf';
            $pdf = $this->get(route('admin.export', $parameters))->assertOk()->assertDownload()->getContent();
            $this->assertStringStartsWith('%PDF-', $pdf, $module);
        }
    }

    public function test_sales_export_follows_search_status_and_date_filters_without_pagination(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        Sale::create(['invoice_number' => 'PJ-OK-2026', 'sale_date' => '2026-10-03', 'status' => 'confirmed', 'total_amount' => 50000]);
        Sale::create(['invoice_number' => 'PJ-DRAFT-2026', 'sale_date' => '2026-10-03', 'status' => 'draft', 'total_amount' => 99999]);
        Sale::create(['invoice_number' => 'PJ-OK-OLD', 'sale_date' => '2026-09-02', 'status' => 'confirmed', 'total_amount' => 20000]);
        $content = $this->get(route('admin.export', [
            'module' => 'sales', 'format' => 'csv', 'q' => 'PJ-', 'status' => 'confirmed',
            'from' => '2026-10-01', 'to' => '2026-10-31',
        ]))->assertOk()->streamedContent();
        $this->assertStringContainsString('PJ-OK-2026', $content);
        $this->assertStringNotContainsString('PJ-DRAFT-2026', $content);
        $this->assertStringNotContainsString('PJ-OK-OLD', $content);
    }
}
