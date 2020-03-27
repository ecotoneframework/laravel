<?php

namespace Ecotone\Laravel;

use Ecotone\Laravel\Commands\ListAllPollableEdnpointsCommand;
use Ecotone\Laravel\Commands\RunPollableEndpointCommand;
use Illuminate\Support\ServiceProvider;

class EcotoneProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->mergeConfigFrom(
            __DIR__.'/config/ecotone.php', 'ecotone'
        );

        if ($this->app->runningInConsole()) {
            $this->commands([
                ListAllPollableEdnpointsCommand::class,
                RunPollableEndpointCommand::class
            ]);
        }
    }
}
