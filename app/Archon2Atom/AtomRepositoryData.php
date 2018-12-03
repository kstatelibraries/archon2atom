<?php

namespace App\Archon2Atom;

use League\Csv\Writer;
use Carbon\Carbon;

class AtomRepositoryData
{

    public function __construct()
    {
        $resultingData = $this->processData();
        $this->exportData($resultingData);
    }

    public function processData()
    {

             $resultingData[] = [
                'legacyId' => '1',
                'uploadLimit' => '2',
                'identifier' => '',
                'authorizedFormOfName' => 'Richard L. D. and Marjorie J. Morse Department of Special Collections',
                'parallelFormsOfName' => '',
                'otherFormsOfName' => 'Morse Department of Special Collections',
                'types' => 'National|Educational Organization',
                'contactPerson' => '',
                'streetAddress' => "506 Hale Library\r\n1117 Mid-Campus Drive North",
                'city' => 'Manhattan',
                'region' => 'Kansas',
                'country' => 'United States',
                'postalCode' => '',
                'telephone' => '(785) 532-7456',
                'fax' => '',
                'email' => 'libsc@ksu.edu',
                'website' => 'http://www.lib.k-state.edu/special-collections',
                'history' => 'Kansas State University is a land-grant university in Manhattan, Kansas, that opened in 1863. In June 1967, Kansas State University Library established a special collections division, which included building an official university archives.',
                'geoculturalContext' => 'The Morse Department of Special Collections is part of Kansas State University Libraries in Manhattan, Kansas.',
                'mandates' => '',
                'internalStructures' => 'For more information on our department, see http://www.lib.k-state.edu/special-collections',
                'collectingPolicies' => '',
                'buildings' => 'The department normally is located in Hale Library.',
                'holdings' => '',
                'findingAids' => '',
                'openingTimes' => 'Normal hours for public access are Monday to Friday, 10:00 a.m. to 5:00 p.m. See http://www.lib.k-state.edu/locations for further details about hours.',
                'accessConditions' => '',
                'disabledAccess' => '',
                'researchServices' => '',
                'reproductionServices' => '',
                'publicFacilities' => '',
                'descriptionIdentifier' => '',
                'institutionIdentifier' => 'US kmk',
                'descriptionRules' => 'DACS',
                'descriptionStatus' => 'Draft',
                'levelOfDetail' => 'Partial',
                'descriptionRevisionHistory' => 'Description created by Cliff Hight on November 29, 2018.',
                'language' => 'en',
                'script' => 'latn',
                'descriptionSources' => '',
                'maintenanceNote' => '',
                'geographicSubregions' => '',
                'thematicAreas' => '',
                'culture' => 'en',
            ];


        $outputData['repository'] = $resultingData;

        return $outputData;
    }

    public function exportData($data)
    {

        $header['repository'] = [
            'legacyId', 'uploadLimit', 'identifier', 'authorizedFormOfName', 
            'parallelFormsOfName', 'otherFormsOfName', 'types', 'contactPerson',
            'streetAddress', 'city', 'region', 'country', 'postalCode', 
            'telephone', 'fax', 'email', 'website', 'history', 
            'geoculturalContext', 'mandates', 'internalStructures', 
            'collectingPolicies', 'buildings', 'holdings', 'findingAids', 
            'openingTimes', 'accessConditions', 'disabledAccess', 
            'researchServices', 'reproductionServices', 'publicFacilities', 
            'descriptionIdentifier', 'institutionIdentifier', 
            'descriptionRules', 'descriptionStatus', 'levelOfDetail', 
            'descriptionRevisionHistory', 'language', 'script', 
            'descriptionSources', 'maintenanceNote', 'geographicSubregions', 
            'thematicAreas', 'culture',
            ];

            $writer_users = Writer::createFromPath('/home/vagrant/code/archon2atom/storage/app/data_import/repository_import.csv', 'w+');
            $writer_users->insertOne($header['repository']);
            $writer_users->insertAll($data['repository']); 
    }
}
