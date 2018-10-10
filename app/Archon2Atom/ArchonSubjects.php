<?php

namespace App\Archon2Atom;

use League\Csv\Writer;

class ArchonSubjects
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
                        'subjectID' => $record['ID'],
                        'Subject' => $record['Subject'],
                        'SubjectTypeID' => $record['SubjectTypeID'],
                        'SubjectSourceID' => $record['SubjectSourceID'],
                        'Identifier' => $record['Identifier'],
                        'ParentID' => $record['ParentID'],
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
            'subjectID', 'Subject', 'SubjectTypeID', 'SubjectSourceID', 'Identifier', 'ParentID', 'Description'
            ];
            
        $writer_content = Writer::createFromPath('/home/vagrant/code/archon2atom/storage/app/data_export/subjects.csv', 'w+');
        $writer_content->insertOne($header['content']);
        $writer_content->insertAll($data['content']); //using an array
    }
}
