<?php

namespace App\Archon2Atom;

use League\Csv\Writer;

class ArchonCountries
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
                        'countryID' => $record['ID'],
                        'CountryName' => $record['CountryName'],
                        'ISOAlpha2' => $record['ISOAlpha2'],
                        'ISOAlpha3' => $record['ISOAlpha3'],
                        'ISONumeric3' => $record['ISONumeric3'],
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
            'countryID', 'CountryName', 'ISOAlpha2', 'ISOAlpha3', 'ISONumeric3',
            ];

        $path = '/home/vagrant/code/archon2atom/storage/app/data_export/';
        $writer_content = Writer::createFromPath($path . 'enum_countries.csv', 'w+');
        $writer_content->insertOne($header['content']);
        $writer_content->insertAll($data['content']);
    }
}
