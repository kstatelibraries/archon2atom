<?php

namespace App\Archon2Atom;

use League\Csv\Writer;

class ArchonAccessions
{

    public function __construct($rawData)
    {
        $resultingData = $this->processData($rawData);
        $this->exportData($resultingData);
    }

    public function processData($data)
    {

        foreach ($data as $recordSet)
        {

            foreach($recordSet as $record) 
            {

                $resultingData[] = [
                    'accessionId' => $record['ID'],
                    'Enabled' => $record['Enabled'], 
                    'AccessionDate' => $record['AccessionDate'],
                    'Title' => $record['Title'],
                    'Identifier' => $record['Identifier'],
                    'InclusiveDates' => $record['InclusiveDates'],
                    'ReceivedExtent' => $record['ReceivedExtent'],
                    'ReceivedExtentUnitID' => $record['ReceivedExtentUnitID'],
                    'UnprocessedExtent' => $record['UnprocessedExtent'],
                    'UnprocessedExtentUnitID' => $record['UnprocessedExtentUnitID'],
                    'MaterialTypeID' => $record['MaterialTypeID'],
                    'ProcessingPriorityID' => $record['ProcessingPriorityID'],
                    'ExpectedCompletionDate' => $record['ExpectedCompletionDate'],
                    'Donor' => $record['Donor'],
                    'DonorContactInformation' => $record['DonorContactInformation'],
                    'DonorNotes' => $record['DonorNotes'],
                    'PhysicalDescription' => $record['PhysicalDescription'],
                    'ScopeContent' => $record['ScopeContent'],
                    'Comments' => $record['Comments'],
                    'PrimaryCreator' => $record['PrimaryCreator'],

                ];

                foreach($record['Creators'] as $creator)
                {
                    $creators[] = 
                    [
                        'accessionId' => $record['ID'],
                        'creatorID' => $creator,
                    ];
                }

                foreach($record['Collections'] as $collection)
                {
                    $collectionData[] = 
                    [
                        'accessionId' => $record['ID'],
                        'collectionID' => $collection,
                    ];
                }

                if(isset($record['Locations'])){
                    foreach($record['Locations'] as $location)
                    {           
                        $locationData[] = 
                        [
                            'accessionId' => $record['ID'],
                            'Location' => $location['Location'],
                            'Content' => $location['Content'],
                            'RangeValue' => $location['RangeValue'],
                            'Section' => $location['Section'],
                            'Shelf' => $location['Shelf'],
                            'Extent' => $location['Extent'],
                            'ExtentUnitID' => $location['ExtentUnitID'],
                            'DisplayPosition' => $location['DisplayPosition'],
                        ];
                    }
                }

                if(isset($record['Subjects'])){
                    foreach($record['Subjects'] as $subject)
                    {               
                        $subjectData[] = 
                        [
                            'accessionId' => $record['ID'],
                            'subjectID' => $subject,
                        ];
                    }
                }

                if(isset($record['Classifications'])){
                    foreach($record['Classifications'] as $classification)
                    {               
                        $classificationData[] = 
                        [
                            'accessionId' => $record['ID'],
                            'classificationID' => $classification,
                        ];
                    }
                }
            }

        }

        $outputData['accessions'] = $resultingData;
        $outputData['locations'] = $locationData;
        $outputData['creators'] = $creators;
        $outputData['subjects'] = $subjectData;
        $outputData['collections'] = $collectionData;
        $outputData['classifications'] = $classificationData;

        return $outputData;
    }

    public function exportData($data)
    {

        $header['accessions'] = [
            'accessionId', 'Enabled', 'AccessionDate', 'Title', 
            'Identifier', 'InclusiveDates', 'ReceivedExtent', 'ReceivedExtentUnitID', 
            'UnprocessedExtent', 'UnprocessedExtentUnitID', 'MaterialTypeID', 'ProcessingPriorityID', 'ExpectedCompletionDate', 
            'Donor', 'DonorContactInformation', 'DonorNotes', 'PhysicalDescription', 'ScopeContent',
            'Comments', 'PrimaryCreator', 
            ];
        $header['subjects'] = [
            'accessionId', 'subjectID'
        ];
        $header['locations'] = [
            'accessionId', 'Location', 'Content', 'RangeValue', 'Section', 'Shelf', 'Extent',
            'ExtentUnitID', 'DisplayPosition'
        ];
        $header['creators'] = [
            'accessionId', 'subjectID'
        ];

        $header['collections'] = [
            'accessionId', 'collectionID'
        ];

        $header['classifications'] = [
            'accessionId', 'classificationID'
        ];


        $writer_accessions = Writer::createFromPath('/home/vagrant/code/archon2atom/storage/app/data_export/accessions.csv', 'w+');
        $writer_accessions->insertOne($header['accessions']);
        $writer_accessions->insertAll($data['accessions']);

        $writer_subjects = Writer::createFromPath('/home/vagrant/code/archon2atom/storage/app/data_export/accessions-subjects.csv', 'w+');
        $writer_subjects->insertOne($header['subjects']);
        $writer_subjects->insertAll($data['subjects']);

        $writer_locations = Writer::createFromPath('/home/vagrant/code/archon2atom/storage/app/data_export/accessions-locations.csv', 'w+');
        $writer_locations->insertOne($header['locations']);
        $writer_locations->insertAll($data['locations']);

        $writer_creators = Writer::createFromPath('/home/vagrant/code/archon2atom/storage/app/data_export/accessions-creators.csv', 'w+');
        $writer_creators->insertOne($header['creators']);
        $writer_creators->insertAll($data['creators']);

        $writer_collections = Writer::createFromPath('/home/vagrant/code/archon2atom/storage/app/data_export/accessions-collections.csv', 'w+');
        $writer_collections->insertOne($header['collections']);
        $writer_collections->insertAll($data['collections']);

        $writer_classifications = Writer::createFromPath('/home/vagrant/code/archon2atom/storage/app/data_export/accessions-classifications.csv', 'w+');
        $writer_classifications->insertOne($header['classifications']);
        $writer_classifications->insertAll($data['classifications']);


    }
}
