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

        foreach ($data as $recordSet) {
            foreach ($recordSet as $record) {
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

                foreach ($record['Creators'] as $creator) {
                    $creators[] =
                    [
                        'accessionId' => $record['ID'],
                        'creatorID' => $creator,
                    ];
                }

                foreach ($record['Collections'] as $collection) {
                    $collectionData[] =
                    [
                        'accessionId' => $record['ID'],
                        'collectionID' => $collection,
                    ];
                }

                if (isset($record['Locations'])) {
                    foreach ($record['Locations'] as $location) {
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

                if (isset($record['Subjects'])) {
                    foreach ($record['Subjects'] as $subject) {
                        $subjectData[] =
                        [
                            'accessionId' => $record['ID'],
                            'subjectID' => $subject,
                        ];
                    }
                }

                if (isset($record['Classifications'])) {
                    foreach ($record['Classifications'] as $classification) {
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
            'Identifier', 'InclusiveDates', 'ReceivedExtent',
            'ReceivedExtentUnitID', 'UnprocessedExtent',
            'UnprocessedExtentUnitID', 'MaterialTypeID', 'ProcessingPriorityID',
            'ExpectedCompletionDate', 'Donor', 'DonorContactInformation',
            'DonorNotes', 'PhysicalDescription', 'ScopeContent','Comments',
            'PrimaryCreator',
            ];
        $header['subjects'] = [
            'accessionId', 'subjectID'
        ];
        $header['locations'] = [
            'accessionId', 'Location', 'Content', 'RangeValue', 'Section', 'Shelf', 'Extent',
            'ExtentUnitID', 'DisplayPosition'
        ];
        $header['creators'] = [
            'accessionId', 'creatorID'
        ];

        $header['collections'] = [
            'accessionId', 'collectionID'
        ];

        $header['classifications'] = [
            'accessionId', 'classificationID'
        ];

        $dataSet = [
            'accessions',
            'subjects',
            'locations',
            'creators',
            'collections',
            'classifications',
        ];

        $path = '/home/vagrant/code/archon2atom/storage/app/data_export/';
        foreach ($dataSet as $item) {
            $name = ($item == 'accessions' ? $item : 'accessions-' . $item);
            $writer = Writer::createFromPath(
                $path . $name . '.csv',
                'w+'
            );
            $writer->insertOne($header[$item]);
            $writer->insertAll($data[$item]);
        }
    }
}
