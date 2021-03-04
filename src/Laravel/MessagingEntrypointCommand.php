<?php


namespace Ecotone\Laravel;


use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ConsoleCommandModule;
use Ecotone\Messaging\Config\ConfiguredMessagingSystem;
use Ecotone\Messaging\Config\ConsoleCommandResultSet;
use Ecotone\Messaging\Gateway\ConsoleCommandRunner;
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
        /** @var ConsoleCommandRunner $consoleCommandRunner */
        $consoleCommandRunner = $configuredMessagingSystem->getGatewayByName(ConsoleCommandRunner::class);

        /** @var ConsoleCommandResultSet $result */
        $result = $consoleCommandRunner->execute($this->signature, $this->getArguments());

        if ($result) {
            $this->table($result->getColumnHeaders(), $result->getRows());
        }

        return 0;
    }
}