<?php

namespace App\Archon2Atom;

use League\Csv\Writer;

class ArchonDigitalFiles
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
                        'digitalFileID' => $record['ID'],
                        'AccessLevel' => $record['AccessLevel'],
                        'DigitalContentID' => $record['DigitalContentID'],
                        'Title' => $record['Title'],
                        'Filename' => $record['Filename'],
                        'FileTypeID' => $record['FileTypeID'],
                        'Bytes' => $record['Bytes'],
                        'DisplayOrder' => $record['DisplayOrder'],
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
            'digitalFileID', 'AccessLevel', 'DigitalContentID', 'Title',
            'Filename', 'FileTypeID', 'Bytes', 'DisplayOrder'
            ];
        $path = '/home/vagrant/code/archon2atom/storage/app/data_export/';
        $writer_content = Writer::createFromPath($path . 'digitalfiles.csv', 'w+');
        $writer_content->insertOne($header['content']);
        $writer_content->insertAll($data['content']);
    }
}
