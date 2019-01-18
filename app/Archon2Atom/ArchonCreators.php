<?php

namespace App\Archon2Atom;

use League\Csv\Writer;

class ArchonCreators
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
                    'creatorID' => $record['ID'],
                    'Name' => $record['Name'],
                    'NameFullerForm' => $record['NameFullerForm'],
                    'NameVariants' => $record['NameVariants'],
                    'CreatorSourceID' => $record['CreatorSourceID'],
                    'CreatorTypeID' => $record['CreatorTypeID'],
                    'Identifier' => $record['Identifier'],
                    'Dates' => $record['Dates'],
                    'BiogHistAuthor' => $record['BiogHistAuthor'],
                    'BiogHist' => $record['BiogHist'],
                    'Sources' => $record['Sources'],
                    'RepositoryID' => $record['RepositoryID'],
                ];

                if (isset($record['CreatorRelationships'])) {
                    foreach ($record['CreatorRelationships'] as $creator) {
                        $creatorrelationships[] =
                        [
                            'creatorID' => $record['ID'],
                            'RelatedCreatorID' => (isset($creator['RelatedCreatorID'])
                                ? $creator['RelatedCreatorID']
                                : null
                            ),
                            'CreatorRelationshipTypeID' => (isset($creator['CreatorRelationshipTypeID'])
                                ? $creator['CreatorRelationshipTypeID']
                                : null
                            ),
                            'Description' => (isset($creator['Description']) ? $creator['Description'] : null),
                        ];
                    }
                }
            }
        }

        $outputData['creators'] = $resultingData;
        $outputData['creatorrelationships'] = (isset($creatorrelationships) ? $creatorrelationships : null);

        return $outputData;
    }

    public function exportData($data)
    {

        $header['creators'] = [
            'creatorID','Name','NameFullerForm','NameVariants','CreatorSourceID','CreatorTypeID',
            'Identifier','Dates','BiogHistAuthor','BiogHist','Sources','RepositoryID',
            ];
        $header['creatorrelationships'] = [
            'creatorID', 'RelatedCreatorID', 'CreatorRelationshipTypeID', 'Description',
        ];

        $dataSet = [
            'creators',
            'creatorrelationships',
        ];

        foreach ($dataSet as $item) {
            $name = ($item == 'creators' ? $item : 'creators-' . $item);
            $writer = Writer::createFromPath(
                '/home/vagrant/code/archon2atom/storage/app/data_export/' .
                $name . '.csv',
                'w+'
            );
            $writer->insertOne($header[$item]);
            $writer->insertAll($data[$item]);
        }
    }
}
