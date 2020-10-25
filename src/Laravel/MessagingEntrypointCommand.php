<?php


namespace Ecotone\Laravel;


use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ConsoleCommandModule;
use Ecotone\Messaging\Gateway\MessagingEntrypoint;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MessagingEntrypointCommand extends Command
{
    private MessagingEntrypoint $messagingEntrypoint;
    private string $requestChannel;
    private string $name;
    private array $parameterNames;

    public function __construct(string $name, string $requestChannel, array $parameterNames, MessagingEntrypoint $messagingEntrypoint)
    {
        $this->name = $name;
        $this->messagingEntrypoint = $messagingEntrypoint;
        $this->requestChannel = $requestChannel;
        $this->parameterNames = $parameterNames;

        parent::__construct();
    }

    protected function configure()
    {
        foreach ($this->parameterNames as $parameterName) {
            $this->addArgument($parameterName, InputArgument::REQUIRED);
        }

        $this
            ->setName($this->name);
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $arguments = [];
        foreach ($input->getArguments() as $argumentName => $value) {
            $arguments[ConsoleCommandModule::ECOTONE_COMMAND_PARAMETER_PREFIX . $argumentName] = $value;
        }

        $this->messagingEntrypoint->send($this->requestChannel, [], $arguments);

        return 0;
    }
}