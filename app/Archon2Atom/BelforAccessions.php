<?php

namespace App\Archon2Atom;

use League\Csv\Writer;
use Carbon\Carbon;

class BelforAccessions
{

    public function __construct($rawData)
    {
        $resultingData = $this->processData($rawData);
        $this->exportData($resultingData);
    }

    public function processData($data)
    {

        foreach ($data['accessionData'] as $record) {
            $title = explode(", ", $record['Title'], 2);

            $resultingData[] = [
                'accessionNumber' => $record['Identifier'],
                'title' => (count($title) > 1 ? $title[1] : $record['Title']),
            ];
        }

        $outputData['accessions'] = $resultingData;

        return $outputData;
    }

    public function exportData($data)
    {

        $header['accessions'] = [
            'accessionNumber','title',
            ];

        $path = '/home/vagrant/code/archon2atom/storage/app/belfor/';
        $writer_users = Writer::createFromPath($path . 'accession_data.csv', 'w+');
        $writer_users->insertOne($header['accessions']);
        $writer_users->insertAll($data['accessions']);
    }
}
