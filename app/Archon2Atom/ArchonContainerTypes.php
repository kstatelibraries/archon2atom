<?php

namespace App\Archon2Atom;

use League\Csv\Writer;

class ArchonContainerTypes
{
    public function __construct($rawData)
    {
        $resultingData = $this->processData($rawData);
        $this->exportData($resultingData);
    }

    public function processData($data)
    {
        foreach($data as $recordSet)
        {
            if(is_null($recordSet))
            {
                continue;
            } else {
                foreach($recordSet as $record)
                {
                    $resultingData[] = [
                        'containerTypeID' => $record['ID'],
                        'ContainerType' => $record['ContainerType'],
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
            'containerTypeID', 'ContainerType',
            ];
            
        $writer_content = Writer::createFromPath('/home/vagrant/code/archon2atom/storage/app/data_export/containertypes.csv', 'w+');
        $writer_content->insertOne($header['content']);
        $writer_content->insertAll($data['content']); //using an array
    }
}
