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

        foreach ($data as $recordSet)
        {

            foreach($recordSet as $record) 
            {

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

                foreach($record['Usergroups'] as $group)
                {
                    $usergroups[] = 
                    [
                        'userID' => $record['ID'],
                        'groupID' => $group,
                    ];
                }

                if(isset($record['Repositories'])){
                    foreach($record['Repositories'] as $repository)
                    {               
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


        $writer_users = Writer::createFromPath('/home/vagrant/code/archon2atom/storage/app/data_export/users.csv', 'w+');
        $writer_users->insertOne($header['users']);
        $writer_users->insertAll($data['users']); 

        $writer_usergroups = Writer::createFromPath('/home/vagrant/code/archon2atom/storage/app/data_export/users-usergroups.csv', 'w+');
        $writer_usergroups->insertOne($header['usergroups']);
        $writer_usergroups->insertAll($data['usergroups']); 

        $writer_repositories = Writer::createFromPath('/home/vagrant/code/archon2atom/storage/app/data_export/users-repository.csv', 'w+');
        $writer_repositories->insertOne($header['repository']);
        $writer_repositories->insertAll($data['repository']);
    }
}
