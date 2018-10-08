<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Archon2Atom\ArchonCollections;

class fetchdata extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'archon:fetchdata';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retreive All Data from Archon';

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
        $collections = new ArchonCollections();

        $this->info(var_dump($collections->fetchData()));
    }
}
