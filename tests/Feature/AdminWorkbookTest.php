<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PharData;
use Tests\TestCase;

class AdminWorkbookTest extends TestCase
{
    use RefreshDatabase;

    public function test_workbook_is_admin_only_and_recap_has_the_single_export_action(): void
    {
        $url = route('admin.reports.workbook', ['from' => '2026-10-01', 'to' => '2026-10-31']);
        $this->get($url)->assertRedirect(route('admin.login'));
        $this->actingAs(User::factory()->create(['is_admin' => false]))->get($url)->assertForbidden();

        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->get(route('admin.reports.index'))->assertOk()->assertSee('Export Laporan Excel');
        $this->get(route('admin.dashboard'))->assertOk()->assertDontSee('Export Laporan Excel');
    }

    public function test_workbook_has_styled_sheets_and_excludes_unconfirmed_sales_from_revenue(): void
    {
        $category = Category::create(['name' => 'Lollipop', 'slug' => 'lollipop', 'is_active' => true]);
        $product = Product::create(['name' => 'Lolly Uji', 'slug' => 'lolly-uji', 'category_id' => $category->id, 'description' => 'Permen', 'price_from' => 12000, 'is_active' => true]);
        $valid = Sale::create(['invoice_number' => 'PJ-OK', 'sale_date' => '2026-10-03', 'status' => 'confirmed', 'total_amount' => 50000]);
        SaleDetail::create(['sale_id' => $valid->id, 'product_id' => $product->id, 'quantity' => 2, 'unit_price' => 25000, 'subtotal' => 50000]);
        Sale::create(['invoice_number' => 'PJ-BATAL', 'sale_date' => '2026-10-03', 'status' => 'cancelled', 'total_amount' => 99999]);
        Sale::create(['invoice_number' => 'PJ-LAMA', 'sale_date' => '2026-09-03', 'status' => 'confirmed', 'total_amount' => 30000]);

        $response = $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get(route('admin.reports.workbook', ['from' => '2026-10-01', 'to' => '2026-10-31']))
            ->assertOk()->assertDownload('Laporan_Operasional_Pelangi_Lollycandy_2026-10.xlsx');
        $content = $response->getContent();
        $this->assertStringStartsWith("PK\x03\x04", $content);

        $path = sys_get_temp_dir().'/pelangi-workbook-'.bin2hex(random_bytes(6)).'.zip';
        file_put_contents($path, $content);
        try {
            $archive = new PharData($path);
            $workbook = $archive['xl/workbook.xml']->getContent();
            $summary = $archive['xl/worksheets/sheet1.xml']->getContent();
            $sales = $archive['xl/worksheets/sheet4.xml']->getContent();
            $styles = $archive['xl/styles.xml']->getContent();
            $this->assertStringContainsString('RINGKASAN LAPORAN', $workbook);
            $this->assertStringContainsString('Item Penjualan', $workbook);
            $this->assertStringNotContainsString('Pembelian', $workbook);
            $this->assertStringContainsString('50000.00', $summary);
            $this->assertStringNotContainsString('99999', $summary);
            $this->assertStringContainsString('PJ-OK', $sales);
            $this->assertStringNotContainsString('PJ-BATAL', $sales);
            $this->assertStringNotContainsString('PJ-LAMA', $sales);
            $this->assertStringContainsString('<autoFilter ref=', $sales);
            $this->assertStringContainsString('state="frozen"', $sales);
            $this->assertStringContainsString('numFmtId="165"', $styles);
            foreach (['[Content_Types].xml', 'xl/workbook.xml', 'xl/styles.xml', 'xl/worksheets/sheet1.xml', 'xl/worksheets/sheet4.xml'] as $part) {
                $this->assertNotFalse(simplexml_load_string($archive[$part]->getContent()), $part.' harus berupa XML valid');
            }
        } finally {
            unlink($path);
        }
    }

    public function test_empty_period_still_downloads_a_valid_summary(): void
    {
        $response = $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get(route('admin.reports.workbook', ['from' => '2026-01-01', 'to' => '2026-01-31']))
            ->assertOk();
        $this->assertStringStartsWith("PK\x03\x04", $response->getContent());
    }
}
