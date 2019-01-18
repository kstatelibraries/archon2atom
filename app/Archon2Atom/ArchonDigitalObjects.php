<?php

namespace App\Archon2Atom;

use League\Csv\Writer;

class ArchonDigitalObjects
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
                    'digitalObjectID' => $record['ID'],
                    'Browsable' => $record['Browsable'],
                    'Title' => $record['Title'],
                    'CollectionID' => $record['CollectionID'],
                    'CollectionContentID' => $record['CollectionContentID'],
                    'Identifier' => $record['Identifier'],
                    'Scope' => $record['Scope'],
                    'PhysicalDescription' => $record['PhysicalDescription'],
                    'Date' => $record['Date'],
                    'Publisher' => $record['Publisher'],
                    'Contributor' => $record['Contributor'],
                    'RightsStatement' => $record['RightsStatement'],
                    'ContentURL' => $record['ContentURL'],
                    'HyperlinkURL' => $record['HyperlinkURL'],
                    'PrimaryCreator' => $record['PrimaryCreator'],

                ];

                foreach ($record['Creators'] as $creator) {
                    $creators[] =
                    [
                        'digitalObjectID' => $record['ID'],
                        'creatorID' => $creator,
                    ];
                }

                foreach ($record['Languages'] as $language) {
                    $languageData[] =
                    [
                        'digitalObjectID' => $record['ID'],
                        'language' => $language,
                    ];
                }

                if (isset($record['Subjects'])) {
                    foreach ($record['Subjects'] as $subject) {
                        $subjectData[] =
                        [
                            'digitalObjectID' => $record['ID'],
                            'subjectID' => $subject,
                        ];
                    }
                }
            }
        }

        $outputData['digitalobjects'] = $resultingData;
        $outputData['creators'] = (isset($creators) ? $creators : null);
        $outputData['subjects'] = (isset($subjectData) ? $subjectData : null);
        $outputData['languages'] = (isset($languageData) ? $languageData : null);

        return $outputData;
    }

    public function exportData($data)
    {
        $header['digitalobjects'] = [
            'digitalObjectID', 'Browsable', 'Title', 'CollectionID',
            'CollectionContentID', 'Identifier', 'Scope', 'PhysicalDescription',
            'Date', 'Publisher', 'Contributor', 'RightsStatement', 'ContentURL',
            'HyperlinkURL', 'PrimaryCreator',
            ];
        $header['subjects'] = [
            'digitalObjectID', 'subjectID'
        ];
        $header['creators'] = [
            'digitalObjectID', 'subjectID'
        ];

        $header['languages'] = [
            'digitalObjectID', 'collectionID'
        ];

        $path = '/home/vagrant/code/archon2atom/storage/app/data_export/';
        $writer_accessions = Writer::createFromPath($path . 'digitalobjects.csv', 'w+');
        $writer_accessions->insertOne($header['digitalobjects']);
        $writer_accessions->insertAll($data['digitalobjects']);

        if (!is_null($data['subjects'])) {
            $writer_accessions = Writer::createFromPath($path . 'digitalobjects-subjects.csv', 'w+');
            $writer_subjects->insertOne($header['subjects']);
            $writer_subjects->insertAll($data['subjects']);
        }
        
        if (!is_null($data['creators'])) {
            $writer_accessions = Writer::createFromPath($path . 'digitalobjects-creators.csv', 'w+');
            $writer_creators->insertOne($header['creators']);
            $writer_creators->insertAll($data['creators']);
        }

        if (!is_null($data['languages'])) {
            $writer_accessions = Writer::createFromPath($path . 'digitalobjects-languages.csv', 'w+');
            $writer_collections->insertOne($header['languages']);
            $writer_collections->insertAll($data['languages']);
        }
    }
}
