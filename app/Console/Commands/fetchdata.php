<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Archon2Atom\ArchonCollections;
use App\Archon2Atom\ArchonConnection;
use App\Archon2Atom\ArchonContent;


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

        $archon = new ArchonConnection();
        $raw_collections = $archon->getCollectionRecords();
        $collections = new ArchonCollections($raw_collections);

        $raw_collection_content = $archon->getAllCollectionContentRecords();
        $content = new ArchonContent($raw_collection_content);

        
        // print_r($raw_collection_content);

        // $resultingData = $collections->processData($collectionsData);
        // $collections->exportData($resultingData);
    }
}
