<?php

namespace App\Integrations\Webmotors;

use Illuminate\Support\ServiceProvider;

class WebmotorsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(VehicleSourceInterface::class, function () {
            return match (config('webmotors.driver', 'fake')) {
                'api' => ApiSiteClient::fromConfig(),
                default => new FakeWebmotorsClient,
            };
        });
    }
}
