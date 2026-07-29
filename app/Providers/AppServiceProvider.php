<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Services\CaseService;
use App\Services\DocumentService;
use App\Services\AppointmentService;
use App\Services\InspectionService;
use App\Services\NotificationService;
use App\Services\VINDecoderService;
use App\Services\PDFGeneratorService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Register services as singletons for better performance
        $this->app->singleton(NotificationService::class, function ($app) {
            return new NotificationService();
        });

        $this->app->singleton(VINDecoderService::class, function ($app) {
            return new VINDecoderService();
        });

        $this->app->singleton(DocumentService::class, function ($app) {
            return new DocumentService();
        });

        $this->app->singleton(PDFGeneratorService::class, function ($app) {
            return new PDFGeneratorService();
        });

        $this->app->singleton(CaseService::class, function ($app) {
            return new CaseService(
                $app->make(NotificationService::class)
            );
        });

        $this->app->singleton(AppointmentService::class, function ($app) {
            return new AppointmentService(
                $app->make(NotificationService::class)
            );
        });

        $this->app->singleton(InspectionService::class, function ($app) {
            return new InspectionService(
                $app->make(NotificationService::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $project_title = '| Rapid Rebuilt LLC';
        View::share('title', $project_title);   
    }
}
