<?php

namespace App\Archon2Atom;

use League\Csv\Writer;

class ArchonCreatorSources
{
    public function __construct($rawData)
    {
        $resultingData = $this->processData($rawData);
        $this->exportData($resultingData);
    }

    public function processData($data)
    {
        foreach ($data as $recordSet) {
            if (is_null($recordSet)) {
                continue;
            } else {
                foreach ($recordSet as $record) {
                    $resultingData[] = [
                        'creatorSourceID' => $record['ID'],
                        'CreatorSource' => $record['CreatorSource'],
                        'SourceAbbreviation' => $record['SourceAbbreviation'],
                    ];
                }
            }
        }
                  
        $outputData['content'] = $resultingData;
        return $outputData;
    }

    public function exportData($data)
    {
        $header['content'] = [
            'creatorSourceID', 'CreatorSource', 'SourceAbbreviation'
            ];

        $path = '/home/vagrant/code/archon2atom/storage/app/data_export/';
        $writer_content = Writer::createFromPath($path . 'enum_creatorsources.csv', 'w+');
        $writer_content->insertOne($header['content']);
        $writer_content->insertAll($data['content']);
    }
}
