<?php

namespace App\Providers;

use App\Models\MarketplaceLink;
use Illuminate\Support\Facades\View;
use Illuminate\View\View as ViewInstance;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.app', function (ViewInstance $view): void {
            $view->with('footerShopeeUrl', MarketplaceLink::query()
                ->where('is_active', true)
                ->where('platform', 'like', '%shopee%')
                ->value('url'));
        });
    }
}
