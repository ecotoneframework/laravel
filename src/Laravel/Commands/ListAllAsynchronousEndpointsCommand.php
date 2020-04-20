<?php

namespace Ecotone\Laravel\Commands;

use Ecotone\Messaging\Config\ConfiguredMessagingSystem;
use Ecotone\Messaging\Config\MessagingSystem;
use Ecotone\Modelling\CommandBus;
use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\Table;

class ListAllAsynchronousEndpointsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ecotone:list-all-asynchronous-endpoints';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all pollable endpoints';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(ConfiguredMessagingSystem $configuredMessagingSystem)
    {
        $repackedNames = [];
        foreach ($configuredMessagingSystem->getListOfSeparatelyRunningConsumers() as $consumerName) {
            $repackedNames[] = [$consumerName];
        }

        $table = new Table($this->output);
        $table
            ->setHeaders(array('Endpoint Names'))
            ->setRows($repackedNames)
        ;
        $table->render();

        return 0;
    }
}
