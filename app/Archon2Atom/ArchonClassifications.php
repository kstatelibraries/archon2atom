<?php

namespace App\Archon2Atom;

use League\Csv\Writer;

class ArchonClassifications
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
                        'classificationID' => $record['ID'],
                        'ParentID' => $record['ParentID'],
                        'ClassificationIdentifier' => $record['ClassificationIdentifier'],
                        'CreatorID' => $record['CreatorID'],
                        'Title' => $record['Title'],
                        'Description' => $record['Description'],
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
            'classificationID', 'ParentID', 'ClassificationIdentifier', 'CreatorID', 'Title', 'Description'
            ];
            
        $path = '/home/vagrant/code/archon2atom/storage/app/data_export/';
        $writer_content = Writer::createFromPath($path . 'classifications.csv', 'w+');
        $writer_content->insertOne($header['content']);
        $writer_content->insertAll($data['content']);
    }
}
