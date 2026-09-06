<?php

namespace App\Providers;

use Illuminate\Mail\Markdown;
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

        // The notification emails are markdown templates using mail::
        // components, but NotificationService sends them through
        // closure-based Mail::send, which renders plain Blade and never
        // registers that namespace (only the Markdown renderer does) -- so
        // every one of them died on "No hint path defined for [mail]" and
        // was swallowed by sendEmail()'s catch. Register it globally, using
        // the renderer's own resolved paths so the published theme in
        // config('mail.markdown.paths') wins and the framework default
        // remains the fallback.
        View::addNamespace('mail', $this->app->make(Markdown::class)->htmlComponentPaths());

        // The customer header's notification bell. Every customer screen
        // renders this partial, so the data rides a composer instead of
        // every controller having to remember to pass it.
        View::composer('layouts.customer.partials.header', function ($view) {
            $user = auth()->user();

            if (!$user) {
                $view->with(['notifications' => collect(), 'unreadNotifications' => 0]);
                return;
            }

            $service = $this->app->make(NotificationService::class);

            $view->with([
                'notifications' => $service->getUserNotifications($user, 5),
                'unreadNotifications' => $service->getUnreadCount($user),
            ]);
        });
    }
}
