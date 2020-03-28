<?php

namespace Ecotone\Laravel;

use Ecotone\Laravel\Commands\ListAllPollableEdnpointsCommand;
use Ecotone\Laravel\Commands\RunPollableEndpointCommand;
use Ecotone\Messaging\Config\Annotation\FileSystemAnnotationRegistrationService;
use Ecotone\Messaging\Config\ApplicationConfiguration;
use Ecotone\Messaging\Config\ConfiguredMessagingSystem;
use Ecotone\Messaging\Config\MessagingSystemConfiguration;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
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
        $this->mergeConfigFrom(
            __DIR__.'/config/ecotone.php', 'ecotone'
        );

        $environment = App::environment();
        $rootCatalog = App::basePath();
        $isCachingConfiguration = $environment === "prod" ? true : Config::get("ecotone.cacheConfiguration");
        $cacheDirectory = $isCachingConfiguration ? App::storagePath() . DIRECTORY_SEPARATOR . "framework" . DIRECTORY_SEPARATOR . "cache" . DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR . "ecotone" : null;
        $serializationMediaType = Config::get("ecotone.serializationMediaType");
        $errorChannel = Config::get("ecotone.errorChannel");

        $applicationConfiguration = ApplicationConfiguration::createWithDefaults()
            ->withEnvironment($environment)
            ->withLoadCatalog(Config::get("ecotone.loadAppNamespaces") ? "app" : "")
            ->withFailFast(false)
            ->withNamespaces(array_merge([FileSystemAnnotationRegistrationService::FRAMEWORK_NAMESPACE], Config::get("ecotone.namespaces")));

        if ($cacheDirectory) {
            $applicationConfiguration = $applicationConfiguration
                ->withCacheDirectoryPath($cacheDirectory);
        }

        if ($serializationMediaType) {
            $applicationConfiguration = $applicationConfiguration
                ->withDefaultSerializationMediaType($serializationMediaType);
        }
        if ($errorChannel) {
            $applicationConfiguration = $applicationConfiguration
                ->withDefaultErrorChannel($errorChannel);
        }

        $configuration = MessagingSystemConfiguration::prepare(
            $rootCatalog,
            new LaravelReferenceSearchService($this->app),
            $applicationConfiguration
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

        if ($this->app->runningInConsole()) {
            $this->commands([
                ListAllPollableEdnpointsCommand::class,
                RunPollableEndpointCommand::class
            ]);
        }
    }
}
