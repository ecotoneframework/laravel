<?php


namespace Ecotone\Laravel;


use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ConsoleCommandModule;
use Ecotone\Messaging\Config\ConfiguredMessagingSystem;
use Ecotone\Messaging\Config\ConsoleCommandResultSet;
use Ecotone\Messaging\Gateway\MessagingEntrypoint;
use Illuminate\Console\Command;

class MessagingEntrypointCommand extends Command
{
    protected $signature;

    private string $requestChannel;

    public function __construct(string $commandName, string $requestChannel)
    {
        $this->signature = $commandName;
        $this->requestChannel = $requestChannel;
        parent::__construct();
    }

    public function handle(ConfiguredMessagingSystem $configuredMessagingSystem)
    {
        /** @var MessagingEntrypoint $messagingEntrypoint */
        $messagingEntrypoint = $configuredMessagingSystem->getGatewayByName(MessagingEntrypoint::class);

        $arguments = [];
        foreach ($this->getArguments() as $argumentName => $value) {
            $arguments[ConsoleCommandModule::ECOTONE_COMMAND_PARAMETER_PREFIX . $argumentName] = $value;
        }

        /** @var ConsoleCommandResultSet $result */
        $result = $messagingEntrypoint->sendWithHeaders([], $arguments, $this->requestChannel);

        if ($result) {
            $this->table($result->getColumnHeaders(), $result->getRows());
        }

        return 0;
    }
}