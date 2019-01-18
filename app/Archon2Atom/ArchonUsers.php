<?php

namespace App\Archon2Atom;

use League\Csv\Writer;

class ArchonUsers
{

    public function __construct($rawData)
    {
        $resultingData = $this->processData($rawData);
        $this->exportData($resultingData);
    }

    public function processData($data)
    {

        foreach ($data as $recordSet) {
            foreach ($recordSet as $record) {
                $resultingData[] = [
                    'ID' => $record['ID'],
                    'Login' => $record['Login'],
                    'Email' => $record['Email'],
                    'FirstName' => $record['FirstName'],
                    'LastName' => $record['LastName'],
                    'DisplayName' => $record['DisplayName'],
                    'IsAdminUser' => $record['IsAdminUser'],
                    'RepositoryLimit' => $record['RepositoryLimit'],
                ];

                foreach ($record['Usergroups'] as $group) {
                    $usergroups[] =
                    [
                        'userID' => $record['ID'],
                        'groupID' => $group,
                    ];
                }

                if (isset($record['Repositories'])) {
                    foreach ($record['Repositories'] as $repository) {
                        $repositoryData[] =
                        [
                            'userID' => $record['ID'],
                            'repositoryID' => $repository,
                        ];
                    }
                }
            }
        }

        $outputData['users'] = $resultingData;
        $outputData['usergroups'] = $usergroups;
        $outputData['repository'] = $repositoryData;

        return $outputData;
    }

    public function exportData($data)
    {

        $header['users'] = [
            'userID', 'Login', 'Email', 'FirstName',
            'LastName', 'DisplayName', 'IsAdminUser', 'RepositoryLimit'
            ];
        $header['usergroups'] = [
            'userID', 'usergroupID'
        ];
        $header['repository'] = [
            'userID', 'repositoryID'
        ];

        $dataSet = [
            'users',
            'usergroups',
            'repository',
        ];

        foreach ($dataSet as $item) {
            $name = ($item == 'users' ? $item : 'users-' . $item);
            $writer = Writer::createFromPath(
                '/home/vagrant/code/archon2atom/storage/app/data_export/' . $name . '.csv',
                'w+'
            );
            $writer->insertOne($header[$item]);
            $writer->insertAll($data[$item]);
        }
    }
}
