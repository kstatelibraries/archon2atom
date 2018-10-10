<?php

namespace App\Archon2Atom;

use League\Csv\Writer;

class ArchonExtentUnits
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
                        'extentID' => $record['ID'],
                        'ExtentUnit' => $record['ExtentUnit'],
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
            'extentID', 'ExtentUnit',
            ];
            
        $writer_content = Writer::createFromPath('/home/vagrant/code/archon2atom/storage/app/data_export/extentunits.csv', 'w+');
        $writer_content->insertOne($header['content']);
        $writer_content->insertAll($data['content']); //using an array
    }
}
