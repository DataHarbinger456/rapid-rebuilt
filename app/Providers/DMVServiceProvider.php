<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\FormDataStorageService;
use App\Validators\DMVFormValidator;

class DMVServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(FormDataStorageService::class, function ($app) {
            return new FormDataStorageService();
        });

        $this->app->singleton(DMVFormValidator::class, function ($app) {
            return new DMVFormValidator();
        });
    }
}