<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Archon2Atom\ArchonCollections;
use App\Archon2Atom\ArchonConnection;
use App\Archon2Atom\ArchonContent;
use App\Archon2Atom\ArchonUserGroups;
use App\Archon2Atom\ArchonSubjectSources;
use App\Archon2Atom\ArchonCreators;
use App\Archon2Atom\ArchonCreatorSources;
use App\Archon2Atom\ArchonExtentUnits;
use App\Archon2Atom\ArchonMaterialTypes;
use App\Archon2Atom\ArchonContainerTypes;
use App\Archon2Atom\ArchonFileTypes;
use App\Archon2Atom\ArchonProcessingPriorities;
use App\Archon2Atom\ArchonCountries;
use App\Archon2Atom\ArchonRepositories;
use App\Archon2Atom\ArchonUsers;
use App\Archon2Atom\ArchonSubjects;
use App\Archon2Atom\ArchonClassifications;
use App\Archon2Atom\ArchonAccessions;
use App\Archon2Atom\ArchonDigitalObjects;
use App\Archon2Atom\ArchonDigitalFiles;
use App\Archon2Atom\AtomAccessions;
use App\Archon2Atom\AtomInformationObjects;
use App\Archon2Atom\AtomRepositoryData;
use App\Archon2Atom\AtomAuthorityRecords;

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

        $raw_user_groups = $archon->getUserGroups();
        $user_groups = new ArchonUserGroups($raw_user_groups);

        $raw_subject_sources = $archon->getSubjectSources();
        $subject_sources = new ArchonSubjectSources($raw_subject_sources);

        $raw_creator_sources = $archon->getCreatorSources();
        $creator_sources = new ArchonCreatorSources($raw_creator_sources);

        $raw_creators = $archon->getCreators();
        $creators = new ArchonCreators($raw_creators);

        $raw_extent_units = $archon->getExtentUnits();
        $extent_units = new ArchonExtentUnits($raw_extent_units);

        $raw_material_types = $archon->getMaterialTypes();
        $material_types = new ArchonMaterialTypes($raw_material_types);

        $raw_container_types = $archon->getContainerTypes();
        $container_types = new ArchonContainerTypes($raw_container_types);

        $raw_file_types = $archon->getFileTypes();
        $file_types = new ArchonFileTypes($raw_file_types);

        $raw_processing_priorities = $archon->getProcessingPriorities();
        $processing_priorities = new ArchonProcessingPriorities($raw_processing_priorities);

        $raw_countries = $archon->getCountries();
        $countries = new ArchonCountries($raw_countries);

        $raw_repositories = $archon->getRepositories();
        $repositories = new ArchonRepositories($raw_repositories);

        $raw_users = $archon->getUsers();
        $users = new ArchonUsers($raw_users);
 
        $raw_subjects = $archon->getSubjects();
        $subjects = new ArchonSubjects($raw_subjects);

        $raw_classifications = $archon->getClassifications();
        $classifications = new ArchonClassifications($raw_classifications);

        $raw_accessions = $archon->getAccessions();
        $accessions = new ArchonAccessions($raw_accessions);
        
        $raw_digital_objects = $archon->getDigitalObjects();
        $digital_objects = new ArchonDigitalObjects($raw_digital_objects);

        $raw_digital_files = $archon->getDigitalFiles();
        $digital_files = new ArchonDigitalFiles($raw_digital_files);

        $archon->getAllDigitalFiles();

        $exportAccessionData = $archon->exportAccessionDataAtom();
        $atom_accession = new AtomAccessions($exportAccessionData);

        $exportInformationObjectsData = $archon->exportInformationObjectsDataAtom();
        $atom_information_objects = new AtomInformationObjects($exportInformationObjectsData);

        $exportRepositoryData = new AtomRepositoryData();

        $exportAuthorityData = $archon->exportAuthorityRecordsDataAtom();
        $atom_authority_records = new AtomAuthorityRecords($exportAuthorityData);

    }
}
