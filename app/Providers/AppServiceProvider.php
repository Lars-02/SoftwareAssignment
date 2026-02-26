<?php

namespace App\Providers;

use App\Domain\Repositories\EquipmentRepositoryInterface;
use App\Domain\Repositories\ImportHistoryRepositoryInterface;
use App\Infrastructure\Repositories\EquipmentRepository;
use App\Infrastructure\Repositories\ImportHistoryRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(EquipmentRepositoryInterface::class, EquipmentRepository::class);
        $this->app->bind(ImportHistoryRepositoryInterface::class, ImportHistoryRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
