<?php

namespace App\Providers;

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
        \Livewire\Livewire::component('⚡stock-transfer-manager', \App\Livewire\StockTransferManager::class);
        \Livewire\Livewire::component('⚡supplier-directory', \App\Livewire\SupplierDirectory::class);
        \Livewire\Livewire::component('⚡purchase-manager', \App\Livewire\PurchaseManager::class);
        \Livewire\Livewire::component('⚡staff-manager', \App\Livewire\StaffManager::class);
        \Livewire\Livewire::component('⚡report-dashboard', \App\Livewire\ReportDashboard::class);
    }
}
