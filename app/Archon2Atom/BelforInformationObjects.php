<?php

namespace App\Archon2Atom;

use League\Csv\Writer;
use Carbon\Carbon;

class BelforInformationObjects
{

    public function __construct($rawData)
    {
        $resultingData = $this->processData($rawData);
        $this->exportData($resultingData);
    }

    public function processData($data)
    {

        // loop through collection data first generating the top level hierarchy

        foreach ($data['collectionData'] as $record)
        {        

            $accessionIds = [];
            $accessionFromCustodialHistory = preg_match_all('/(?:([UupP]{1}\d{4}\.\d{1,3})|(\d{4}-\d{2}\.\d{1,3}))/', $record['CustodialHistory'], $accessionIds,PREG_SET_ORDER);
            $accessions = collect($accessionIds)->collapse()->unique()->filter(function ($value) {
                return $value != '';
            })->toArray();

            $resultingData[] = [
                'title' => $record['Title'],
                'accessionId' => implode('|',$accessions),
                'archivalHistory' => $record['CustodialHistory'],
            ];

        }

        $outputData['infoObjects'] = $resultingData;

        return $outputData;
    }

    public function exportData($data)
    {

        $header['infoObjects'] = [
            'title', 'accessionId', 'archivalHistory'
            ];

        $writer = Writer::createFromPath('/home/vagrant/code/archon2atom/storage/app/belfor/collections_output.csv', 'w+');
        $writer->insertOne($header['infoObjects']);
        $writer->insertAll($data['infoObjects']);

    }

}
