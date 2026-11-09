<?php

namespace App\Providers;

use App\Models\MarketplaceLink;
use App\Support\BusinessContact;
use Illuminate\Support\Facades\URL;
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
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        View::composer('layouts.app', function (ViewInstance $view): void {
            $view->with('footerShopeeUrl', MarketplaceLink::query()
                ->where('is_active', true)
                ->where('platform', 'like', '%shopee%')
                ->value('url'));
            $view->with('businessWhatsAppUrl', BusinessContact::whatsAppUrl());
        });
    }
}
