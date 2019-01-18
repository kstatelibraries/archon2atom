<?php

namespace App\Archon2Atom;

use League\Csv\Writer;

class ArchonMaterialTypes
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
                        'materialTypeID' => $record['ID'],
                        'MaterialType' => $record['MaterialType'],
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
            'materialTypeID', 'MaterialType',
            ];
            
        $path = '/home/vagrant/code/archon2atom/storage/app/data_export/';
        $writer_content = Writer::createFromPath($path . 'enum_materialtypes.csv', 'w+');
        $writer_content->insertOne($header['content']);
        $writer_content->insertAll($data['content']);
    }
}
