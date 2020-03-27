<?php

namespace Ecotone\Laravel\Commands;

use Illuminate\Console\Command;

class RunPollableEndpointCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ecotone:run-endpoint {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs Ecotone pollable endpoint';

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
    public function handle()
    {
        //
    }
}
