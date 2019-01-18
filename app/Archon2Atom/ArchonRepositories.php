<?php

namespace App\Archon2Atom;

use League\Csv\Writer;

class ArchonRepositories
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
                        'repositoryID' => $record['ID'],
                        'Name' => $record['Name'],
                        'Code' => $record['Code'],
                        'Address' => $record['Address'],
                        'Address2' => $record['Address2'],
                        'City' => $record['City'],
                        'State' => $record['State'],
                        'CountryID' => $record['CountryID'],
                        'ZIPCode' => $record['ZIPCode'],
                        'ZIPPlusFour' => $record['ZIPPlusFour'],
                        'Phone' => $record['Phone'],
                        'PhoneExtension' => $record['PhoneExtension'],
                        'Fax' => $record['Fax'],
                        'Email' => $record['Email'],
                        'URL' => $record['URL'],
                        'EmailSignature' => $record['EmailSignature'],
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
            'repositoryID', 'Name', 'Code', 'Address', 'Address2', 'City',
            'State', 'CountryID', 'ZIPCode', 'ZIPPlusFour', 'Phone',
            'PhoneExtension', 'Fax', 'Email', 'URL', 'EmailSignature'
        ];
            
        $path = '/home/vagrant/code/archon2atom/storage/app/data_export/';
        $writer_content = Writer::createFromPath($path . 'repositories.csv', 'w+');
        $writer_content->insertOne($header['content']);
        $writer_content->insertAll($data['content']);
    }
}
