<?php

namespace App\Archon2Atom;

use GuzzleHttp\Client;

class ArchonConnection
{

    protected $client;
    protected $archon_session;
    protected $collectionData;



    const ADMIN_LOGIN_ENDPOINT = "?p=core/authenticate";

    const ENUM_USER_GROUPS_ENDPOINT = "?p=core/enums&enum_type=usergroups";
    const ENUM_SUBJECT_SOURCES_ENDPOINT = "?p=core/enums&enum_type=subjectsources";
    const ENUM_CREATOR_SOURCES_ENDPOINT = "?p=core/enums&enum_type=creatorsources";
    const ENUM_EXTENT_UNITS_ENDPOINT = "?p=core/enums&enum_type=extentunits";
    const ENUM_MATERIAL_TYPES_ENDPOINT = "?p=core/enums&enum_type=materialtypes";
    const ENUM_ACCESSION_TYPES_ENDPOINT = "?p=core/enums&enum_type=materialtypes";
    const ENUM_CONTAINER_TYPES_ENDPOINT = "?p=core/enums&enum_type=containertypes";
    const ENUM_FILE_TYPES_ENDPOINT = "?p=core/enums&enum_type=filetypes";
    const ENUM_PROCESSING_PRIORITIES_ENDPOINT = "?p=core/enums&enum_type=processingpriorities";
    const ENUM_COUNTRIES_ENDPOINT = "?p=core/enums&enum_type=countries";

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

    public function getCreatorSources()
    {
        return $this->fetchData(self::ENUM_CREATOR_SOURCES_ENDPOINT, 1);
    }

    public function getExtentUnits()
    {
        return $this->fetchData(self::ENUM_EXTENT_UNITS_ENDPOINT, 1);
    }

    public function getMaterialTypes()
    {
        return $this->fetchData(self::ENUM_MATERIAL_TYPES_ENDPOINT, 1);
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
                $collectionContentRecords[$data['ID']] = $this->getCollectionContentRecords($data['ID'])[0];
            }
        }
        return $collectionContentRecords;
    }
}