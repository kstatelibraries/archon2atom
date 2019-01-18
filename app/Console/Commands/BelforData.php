<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Archon2Atom\ArchonConnection;
use App\Archon2Atom\BelforAccessions;
use App\Archon2Atom\BelforInformationObjects;

class BelforData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'archon:belfordata';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retreive Data for Belfor use';

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

        $exportAccessionData = $archon->exportAccessionDataBelfor();
        $belfor_accession = new BelforAccessions($exportAccessionData);

        $exportInformationObjectsData = $archon->exportInformationObjectsDataBelfor();
        $belfor_information_objects = new BelforInformationObjects($exportInformationObjectsData);
    }
}
