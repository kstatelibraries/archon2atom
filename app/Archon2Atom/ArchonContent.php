<?php

namespace App\Archon2Atom;

use GuzzleHttp\Client;
use League\Csv\Writer;

class ArchonContent
{

    protected $client;
    protected $jar;
    protected $archon_session;

    public function __construct($rawData)
    {
        $resultingData = $this->processData($rawData);
        $this->exportData($resultingData);
    }

    public function processData($data)
    {

        // print_r($data);

        foreach ($data as $recordSet) {
            if (is_null($recordSet)) {
                continue;
            } else {
                foreach ($recordSet as $record) {
                    $resultingData[] = [
                        'CollectionID' => $record['CollectionID'],
                        'ID' => $record['ID'],
                        'Title' => $record['Title'],
                        'PrivateTitle' => $record['PrivateTitle'],
                        'Date' => $record['Date'],
                        'Description' => $record['Description'],
                        'RootContentID' => $record['RootContentID'],
                        'ParentID' => $record['ParentID'],
                        'ContainsContent' => $record['ContainsContent'],
                        'Enabled' => $record['Enabled'],
                        'SortOrder' => $record['SortOrder'],
                        'ContentType' => $record['ContentType'],
                        'UniqueID' => $record['UniqueID'],
                        'EADLevel' => $record['EADLevel'],
                        'OtherLevel' => $record['OtherLevel'],
                        'ContainerTypeID' => $record['ContainerTypeID'],
                        'ContainerIndicator' => $record['ContainerIndicator'],
                    ];


                    if (isset($record['Creators'])) {
                        foreach ($record['Creators'] as $creator) {
                            $creators[] =
                            [
                                'collectionID' => $record['ID'],
                                'creatorID' => $creator,
                            ];
                        }
                    }

                    if (isset($record['Notes'])) {
                        foreach ($record['Notes'] as $note) {
                            $notesData[] =
                            [
                                'collectionID' => $record['ID'],
                                'notes' => $note,
                            ];
                        }
                    }

                    if (isset($record['Subjects'])) {
                        foreach ($record['Subjects'] as $subject) {
                            $subjectData[] =
                            [
                                'collectionID' => $record['ID'],
                                'subjectID' => $subject,
                            ];
                        }
                    }
                }
            }
        }

                    

        $outputData['content'] = $resultingData;
        $outputData['notes'] = (isset($notesData) ? $notesData : null);
        $outputData['creators'] = (isset($creators) ? $creators : null);
        $outputData['subjects'] = (isset($subjectData) ? $subjectData : null);

        return $outputData;
    }

    public function exportData($data)
    {

        $header['content'] = [
            'CollectionID', 'ID', 'Title', 'PrivateTitle', 'Date', 'Description', 'RootContentID', 'ParentID',
            'ContainsContent', 'Enabled', 'SortOrder', 'ContentType', 'UniqueID', 'EADLevel', 'OtherLevel',
            'ContainerTypeID', 'ContainerIndicator'
            ];
        $header['subjects'] = [
            'collectionID', 'subjectID'
        ];
        $header['notes'] = [
            'collectionID', 'Notes'
        ];
        $header['creators'] = [
            'collectionID', 'creatorID'
        ];


        $path = '/home/vagrant/code/archon2atom/storage/app/data_export/';
        $writer_content = Writer::createFromPath($path . 'collectioncontent.csv', 'w+');
        $writer_content->insertOne($header['content']);
        $writer_content->insertAll($data['content']);

        if (!is_null($data['subjects'])) {
            $writer_subjects = Writer::createFromPath($path . 'collectioncontent-subjects.csv', 'w+');
            $writer_subjects->insertOne($header['subjects']);
            $writer_subjects->insertAll($data['subjects']);
        }

        if (!is_null($data['notes'])) {
            $writer_notes = Writer::createFromPath($path . 'collectioncontent-notes.csv', 'w+');
            $writer_notes->insertOne($header['notes']);
            $writer_notes->insertAll($data['notes']);
        }

        if (!is_null($data['creators'])) {
            $writer_creators = Writer::createFromPath($path . 'collectioncontent-creators.csv', 'w+');
            $writer_creators->insertOne($header['creators']);
            $writer_creators->insertAll($data['creators']);
        }
    }
}
