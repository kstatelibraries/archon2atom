<?php

namespace App\Archon2Atom;

use GuzzleHttp\Client;

class ArchonConnection
{

    protected $client;
    protected $archon_session;
    protected $collectionData;
    protected $digitalFiles;
    protected $enumExtentUnits;
    protected $enumProcessingPriorities;
    protected $creators;
    protected $enumMaterialTypes;
    protected $accessionData;
    protected $subjects;
    protected $collectionContentData;



    const ADMIN_LOGIN_ENDPOINT = "?p=core/authenticate";

    const ENUM_USER_GROUPS_ENDPOINT = "?p=core/enums&enum_type=usergroups";
    const ENUM_SUBJECT_SOURCES_ENDPOINT = "?p=core/enums&enum_type=subjectsources";
    const ENUM_CREATOR_SOURCES_ENDPOINT = "?p=core/enums&enum_type=creatorsources";
    const ENUM_EXTENT_UNITS_ENDPOINT = "?p=core/enums&enum_type=extentunits";
    const ENUM_MATERIAL_TYPES_ENDPOINT = "?p=core/enums&enum_type=materialtypes";
    const ENUM_FILE_TYPES_ENDPOINT = "?p=core/enums&enum_type=filetypes";
    const ENUM_PROCESSING_PRIORITIES_ENDPOINT = "?p=core/enums&enum_type=processingpriorities";
    const ENUM_COUNTRIES_ENDPOINT = "?p=core/enums&enum_type=countries";
    const ENUM_CONTAINER_TYPES_ENDPOINT = "?p=core/enums&enum_type=containertypes";
    /* 
    *
    * The following was in ArchivesSpace's export fields, but the data does not exist as an exportable
    * field in archon.
    *
    */ 
    #const ENUM_ACCESSION_TYPES_ENDPOINT = "?p=core/enums&enum_type=materialtypes";


    const REPOSITORY_ENDPOINT = "?p=core/repositories";
    const USER_ENDPOINT = "?p=core/users";
    const SUBJECT_ENDPOINT = "?p=core/subjects";
    const CREATOR_ENDPOINT = "?p=core/creators";
    const CLASSIFICATION_ENDPOINT = "?p=core/classifications";
    const ACCESSION_ENDPOINT = "?p=core/accessions";
    const DIGITAL_OBJECT_ENDPOINT = "?p=core/digitalcontent";
    const DIGITAL_FILE_ENDPOINT = "?p=core/digitalfiles";
    const DIGITAL_FILE_BLOB_ENDPOINT = "?p=core/digitalfileblob";
    const COLLECTION_ENDPOINT = "?p=core/collections";
    const COLLECTION_CONTENT_ENDPOINT = "?p=core/content";

    public function __construct()
    {

        $this->client = new \GuzzleHttp\Client([
            'cookies' => true,
            'base_uri' => env('ARCHON_BASE_URL'),
        ]);

        $this->authenticateArchon();
    }

    public function authenticateArchon() 
    {
        $auth = $this->client->POST(self::ADMIN_LOGIN_ENDPOINT,[
            'auth' => [env('ARCHON_USERNAME'), env('ARCHON_PASSWORD'), 'basic'],
        ]);

        $cookies = $this->client->getConfig('cookies');
        $cookie_data = $cookies->toArray();
        $this->archon_session = $cookie_data[0]['Value'];
    
}
    public function fetchData($endpoint, $batch_start = 1)
    {
        do { 
            $response = $this->client->GET($endpoint . '&batch_start=' . $batch_start, [
                'headers' => [
                    'session' => $this->archon_session,
                ]
            ]);

            if(strpos((string)$response->getBody(), "No matching record") === false) {
                $data[] = json_decode($response->getBody(), true);  
                $batch_start += 100;
            } else {
                break;
            }
        } while (true);

        if(!isset($data)) {
            return false;
        } else {
            return $data;
        }
    }


    public function getUserGroups()
    {
        return $this->fetchData(self::ENUM_USER_GROUPS_ENDPOINT, 1);
    }

    public function getSubjectSources()
    {
        return $this->fetchData(self::ENUM_SUBJECT_SOURCES_ENDPOINT, 1);
    }

    public function getCreators()
    {
        $this->creators = $this->fetchData(self::CREATOR_ENDPOINT, 1);
        return $this->creators;
    }

    public function getCreatorSources()
    {
        return $this->fetchData(self::ENUM_CREATOR_SOURCES_ENDPOINT, 1);
    }

    public function getExtentUnits()
    {
        $this->enumExtentUnits = $this->fetchData(self::ENUM_EXTENT_UNITS_ENDPOINT, 1);
        return $this->enumExtentUnits;
    }

    public function getMaterialTypes()
    {
        $this->enumMaterialTypes = $this->fetchData(self::ENUM_MATERIAL_TYPES_ENDPOINT, 1);
        return $this->enumMaterialTypes;
    }

    public function getContainerTypes()
    {
        return $this->fetchData(self::ENUM_CONTAINER_TYPES_ENDPOINT, 1);
    }

    public function getFileTypes()
    {
        return $this->fetchData(self::ENUM_FILE_TYPES_ENDPOINT, 1);
    }

    public function getProcessingPriorities()
    {
        $this->enumProcessingPriorities = $this->fetchData(self::ENUM_PROCESSING_PRIORITIES_ENDPOINT, 1);
        return $this->enumProcessingPriorities;
    }

    public function getCountries()
    {
        return $this->fetchData(self::ENUM_COUNTRIES_ENDPOINT, 1);
    }

    public function getRepositories()
    {
        return $this->fetchData(self::REPOSITORY_ENDPOINT, 1);
    }

    public function getUsers()
    {
        return $this->fetchData(self::USER_ENDPOINT, 1);
    }

    public function getSubjects()
    {
        $this->subjects = $this->fetchData(self::SUBJECT_ENDPOINT, 1);
        return $this->subjects;
    }

    public function getClassifications()
    {
        return $this->fetchData(self::CLASSIFICATION_ENDPOINT, 1);
    }

    public function getAccessions()
    {
        $this->accessionData = $this->fetchData(self::ACCESSION_ENDPOINT, 1);
        return $this->accessionData;
    }

    public function getDigitalObjects()
    {
        return $this->fetchData(self::DIGITAL_OBJECT_ENDPOINT, 1);
    }

    public function getDigitalFiles()
    {
        $this->digitalFiles = $this->fetchData(self::DIGITAL_FILE_ENDPOINT, 1);
        return $this->digitalFiles;
    }

    public function getCollectionRecords()
    {
        $this->collectionData = $this->fetchData(self::COLLECTION_ENDPOINT, 1 );
        return $this->collectionData;
    }

    public function getCollectionContentRecords($collectionID)
    {
        $endpoint = self::COLLECTION_CONTENT_ENDPOINT . '&cid=' . $collectionID;
        return $this->fetchData($endpoint, 1 );
    }

    public function getAllCollectionContentRecords()
    {

        if($this->collectionData == null) 
        {
            $this->getCollectionRecords();
        }

        foreach($this->collectionData as $collection)
        {
            foreach($collection as $data)
            {
                $collectionContentRecords[$data['ID']] = collect($this->getCollectionContentRecords($data['ID']))->collapse()->keyBy('ID')->sortBy('ParentID')->toArray();
            }
        }

        $this->collectionContentData = $collectionContentRecords;
        return $this->collectionContentData;
    }

    public function getAllDigitalFiles()
    {
        if($this->digitalFiles == null) 
        {
           $this->getDigitalFiles();
        }

        foreach($this->digitalFiles as $collection)
        {
            foreach($collection as $file) 
            {
                $url = self::DIGITAL_FILE_BLOB_ENDPOINT . '&batch_start=1&fileid=' . $file['ID'];
                $response = $this->client->get($url, [
                    'headers' => [
                        'session' => $this->archon_session,
                        ],
                    'sink' => '/home/vagrant/code/archon2atom/storage/app/data_export/files/' . $file['Filename'],
                ]);
            }
        }
    }

    public function exportAccessionDataAtom()
    {
        if($this->enumExtentUnits == null) 
        {
           $this->getExtentUnits();
        }
        
        if ($this->enumProcessingPriorities == null) 
        {
            $this->getProcessingPriorities();
        }

        if($this->creators == null)
        {
            $this->getCreators();
        }

        if($this->enumMaterialTypes == null)
        {
            $this->getMaterialTypes();
        }

        if($this->accessionData == null)
        {
            $this->getAccessions();
        }

        $accessionExportData = 
            [
                'accessionData' => collect($this->accessionData)->collapse()->keyBy('ID')->sort()->toArray(),
                'extentUnits' => collect($this->enumExtentUnits)->collapse()->keyBy('ID')->toArray(),
                'creators' => collect($this->creators)->collapse()->keyBy('ID')->sort()->toArray(),
                'materialTypes' => collect($this->enumMaterialTypes)->collapse()->keyBy('ID')->sort()->toArray(),
                'processingPriorities' => collect($this->enumProcessingPriorities)->collapse()->keyBy('ID')->sort()->toArray(),
            ];

        return $accessionExportData;
    }

     public function exportInformationObjectsDataAtom()
    {
        if($this->enumExtentUnits == null)
        {
           $this->getExtentUnits();
        }

        if ($this->enumProcessingPriorities == null)
        {
            $this->getProcessingPriorities();
        }

        if($this->creators == null)
        {
            $this->getCreators();
        }

        if($this->enumMaterialTypes == null)
        {
            $this->getMaterialTypes();
        }

        if($this->subjects == null)
        {
            $this->getSubjects();
        }

        if($this->collectionData == null)
        {
            $this->getCollectionRecords();
        }

        if($this->collectionContentData == null)
        {
            $this->getAllCollectionContentRecords();
        }

        $accessionExportData =
            [
                'collectionData' => collect($this->collectionData)->collapse()->keyBy('ID')->sort()->toArray(),
                'extentUnits' => collect($this->enumExtentUnits)->collapse()->keyBy('ID')->toArray(),
                'creators' => collect($this->creators)->collapse()->keyBy('ID')->sort()->toArray(),
                'subjects' => collect($this->subjects)->collapse()->keyBy('ID')->sort()->toArray(),
                'collectionContentData' => collect($this->collectionContentData)->collapse()->keyBy('ID')->sortBy('ParentID')->toArray(),
            ];

        return $accessionExportData;
    }
}