<?php

namespace Ecotone\Laravel;

use Ecotone\Laravel\Commands\ListAllPollableEdnpointsCommand;
use Ecotone\Laravel\Commands\RunPollableEndpointCommand;
use Ecotone\Messaging\Config\Annotation\FileSystemAnnotationRegistrationService;
use Ecotone\Messaging\Config\ApplicationConfiguration;
use Ecotone\Messaging\Config\ConfiguredMessagingSystem;
use Ecotone\Messaging\Config\MessagingSystemConfiguration;
use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;

class EcotoneProvider extends ServiceProvider
{
    const MESSAGING_SYSTEM_REFERENCE = ConfiguredMessagingSystem::class;

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $environment = App::environment();
        $rootCatalog = App::basePath();
        $cacheDirectory = App::storagePath("framework/cache") . DIRECTORY_SEPARATOR . "ecotone";

        $configuration = MessagingSystemConfiguration::prepare(
            $rootCatalog,
            new LaravelReferenceSearchService($this->app),
            ApplicationConfiguration::createWithDefaults()
                ->withEnvironment($environment)
                ->withLoadCatalog("app")
                ->withCacheDirectoryPath($cacheDirectory)
                ->withNamespaces([FileSystemAnnotationRegistrationService::FRAMEWORK_NAMESPACE])
        );

        foreach ($configuration->getRegisteredGateways() as $registeredGateway) {
            $this->app->singleton($registeredGateway->getReferenceName(), function ($app) use ($registeredGateway, $cacheDirectory) {
                return ProxyGenerator::createFor(
                    $registeredGateway->getReferenceName(),
                    $app,
                    $registeredGateway->getInterfaceName(),
                    $cacheDirectory
                );
            });
        }

        $this->app->singleton(self::MESSAGING_SYSTEM_REFERENCE, $configuration->buildMessagingSystemFromConfiguration(new LaravelReferenceSearchService($this->app)));
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/config/ecotone.php' => config_path('ecotone.php'),
        ]);
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
