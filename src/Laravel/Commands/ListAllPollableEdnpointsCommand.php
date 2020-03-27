<?php

namespace Ecotone\Laravel\Commands;

use Illuminate\Console\Command;

class ListAllPollableEdnpointsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ecotone:list-all-pollable-endpoints';

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
    public function handle()
    {
        //
    }
}
