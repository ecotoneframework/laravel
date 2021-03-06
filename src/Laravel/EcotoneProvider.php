<?php

namespace Ecotone\Laravel;

use Ecotone\Laravel\Commands\ListAllAsynchronousEndpointsCommand;
use Ecotone\Laravel\Commands\RunAsynchronousEndpointCommand;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ConsoleCommandModule;
use Ecotone\Messaging\Config\ConfiguredMessagingSystem;
use Ecotone\Messaging\Config\ConsoleCommandResultSet;
use Ecotone\Messaging\Config\MessagingSystemConfiguration;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\ConfigurationVariableService;
use Ecotone\Messaging\Gateway\ConsoleCommandRunner;
use Ecotone\Messaging\Gateway\MessagingEntrypoint;
use Ecotone\Messaging\Handler\Logger\EchoLogger;
use Ecotone\Messaging\Handler\Logger\LoggingHandlerBuilder;
use Ecotone\Messaging\Handler\Recoverability\RetryTemplateBuilder;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Console\ClosureCommand;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

class EcotoneProvider extends ServiceProvider
{
    const FRAMEWORK_NAMESPACE        = "Ecotone";
    const MESSAGING_SYSTEM_REFERENCE = ConfiguredMessagingSystem::class;

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/config/ecotone.php', 'ecotone'
        );

        $environment            = App::environment();
        $rootCatalog            = App::basePath();
        $isCachingConfiguration = $environment === "prod" ? true : Config::get("ecotone.cacheConfiguration");
        $cacheDirectory         = App::storagePath() . DIRECTORY_SEPARATOR . "framework" . DIRECTORY_SEPARATOR . "cache" . DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR . "ecotone";
        if (!is_dir($cacheDirectory)) {
            mkdir($cacheDirectory, 0775, true);
        }

        $serializationMediaType = Config::get("ecotone.defaultSerializationMediaType");

        $errorChannel = Config::get("ecotone.defaultErrorChannel");

        $applicationConfiguration = ServiceConfiguration::createWithDefaults()
            ->withEnvironment($environment)
            ->withLoadCatalog(Config::get("ecotone.loadAppNamespaces") ? "app" : "")
            ->withFailFast(false)
            ->withNamespaces(array_merge([self::FRAMEWORK_NAMESPACE], Config::get("ecotone.namespaces")));

        if ($isCachingConfiguration) {
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

        $retryTemplate = Config::get("ecotone.defaultConnectionExceptionRetry");
        if ($retryTemplate) {
            $applicationConfiguration = $applicationConfiguration
                ->withConnectionRetryTemplate(
                    RetryTemplateBuilder::exponentialBackoffWithMaxDelay(
                        $retryTemplate["initialDelay"],
                        $retryTemplate["maxAttempts"],
                        $retryTemplate["multiplier"]
                    )
                );
        }

        $configuration = MessagingSystemConfiguration::prepare(
            $rootCatalog,
            new LaravelReferenceSearchService($this->app),
            new LaravelConfigurationVariableService(),
            $applicationConfiguration,
            $isCachingConfiguration
        );

        $this->app->singleton(
            ConfigurationVariableService::REFERENCE_NAME, function () use ($configuration) {
            return new LaravelConfigurationVariableService();
        });

        foreach ($configuration->getRegisteredGateways() as $registeredGateway) {
            $this->app->singleton(
                $registeredGateway->getReferenceName(), function ($app) use ($registeredGateway, $cacheDirectory) {
                return ProxyGenerator::createFor(
                    $registeredGateway->getReferenceName(),
                    $app,
                    $registeredGateway->getInterfaceName(),
                    $cacheDirectory
                );
            }
            );
        }

        if ($this->app->runningInConsole()) {
            foreach ($configuration->getRegisteredConsoleCommands() as $oneTimeCommandConfiguration) {
                $commandName = $oneTimeCommandConfiguration->getName();
                foreach ($oneTimeCommandConfiguration->getParameters() as $parameter) {
                    if ($parameter->getDefaultValue()) {
                        $commandName .= ' {' . $parameter->getName() . '}=' . $parameter->getDefaultValue();
                    } else {
                        $commandName .= ' {' . $parameter->getName() . '}';
                    }
                }

                Artisan::command(
                    $commandName, function (ConfiguredMessagingSystem $configuredMessagingSystem) {
                    /** @var ConsoleCommandRunner $consoleCommandRunner */
                    $consoleCommandRunner = $configuredMessagingSystem->getGatewayByName(ConsoleCommandRunner::class);

                    /** @var ClosureCommand $self */
                    $self      = $this;

                    /** @var ConsoleCommandResultSet $result */
                    $result = $consoleCommandRunner->execute($this->name, $self->arguments());

                    if ($result) {
                        $self->table($result->getColumnHeaders(), $result->getRows());
                    }

                    return 0;
                }
                );
            }
        }

        $this->app->singleton(
            self::MESSAGING_SYSTEM_REFERENCE, function () use ($configuration) {
            return $configuration->buildMessagingSystemFromConfiguration(new LaravelReferenceSearchService($this->app));
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes(
            [
                __DIR__ . '/config/ecotone.php' => config_path('ecotone.php'),
            ]
        );

        if (!$this->app->has(LoggingHandlerBuilder::LOGGER_REFERENCE)) {
            $this->app->singleton(
                LoggingHandlerBuilder::LOGGER_REFERENCE, function (Application $app) {
                if ($app->runningInConsole()) {
                    return new CombinedLogger($app->get("log"), new EchoLogger());
                }

                return $app->get("log");
            }
            );
        }
    }
}
