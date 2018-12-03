<?php

namespace App\Archon2Atom;

use League\Csv\Writer;
use Carbon\Carbon;

class AtomAuthorityRecords
{

    public function __construct($rawData)
    {
        $resultingData = $this->processData($rawData);
        $this->exportData($resultingData);
    }

    public function processData($data)
    {

        foreach ($data['creatorData'] as $creator)
        {

            // print_r($creator);
           
            $creatorSource = ($creator['CreatorSourceID'] == '' ? '' : 'Creator Source: ' . $data['creatorSourceData'][$creator['CreatorSourceID']]['CreatorSource']);
            $biogHistAuthor = ($creator['BiogHistAuthor'] == '' ? '' : 'Biographical/Historical Note Author: ' . $creator['BiogHistAuthor']);
            $creatorSourceBiogAuthorSeperator = ($creatorSource !== '' && $biogHistAuthor !== '' ? "\r\n" : '');

            $resultingData['authorityRecords'][] = [
                'culture' => 'en',
                'typeOfEntity' => $this->typeOfEntity($creator['CreatorTypeID']),
                'authorizedFormOfName' => $creator['Name'],
                'corporateBodyIdentifiers' => '',
                'datesOfExistence' => $creator['Dates'],
                'history' => $creator['BiogHist'],
                'places' => '',
                'legalStatus' => '',
                'functions' => '',
                'mandates' => '',
                'internalStructures' => '',
                'generalContext' => '',
                'descriptionIdentifier' => '',
                'institutionIdentifier' => '',
                'rules' => '',
                'status' => '',
                'levelOfDetail' => '',
                'revisionHistory' => '',
                'sources' => $creator['Sources'],
                'maintenanceNotes' => $creatorSource . $creatorSourceBiogAuthorSeperator . $biogHistAuthor,
            ];

            if($creator['NameFullerForm'] !== '')
            {
                $resultingData['authorityRecordsAliases'][] = [
                    'parentAuthorizedFormOfName' => $creator['Name'],
                    'alternateForm' => $creator['NameFullerForm'],
                    'formType' => 'other',
                    'culture' => 'en',
                ];
            }

            if($creator['NameVariants'] !== '')
            {
                $nameVariants = explode(';', $creator['NameVariants']);


                foreach( $nameVariants as $name )
                {
                    $resultingData['authorityRecordsAliases'][] = [
                        'parentAuthorizedFormOfName' => $creator['Name'],
                        'alternateForm' => trim($name),
                        'formType' => 'other',
                        'culture' => 'en',
                    ];
                }
            }


            if(isset($creator['CreatorRelationships']))
            {

                foreach($creator['CreatorRelationships'] as $relationship)
                {
                    $resultingData['authorityRecordsRelationships'][] = [
                        'sourceAuthorizedFormOfName' => $creator['Name'],
                        'targetAuthorizedFormOfName' => $data['creatorData'][$relationship['RelatedCreatorID']]['Name'],
                        'category' => $this->creatorRelationshipMapping($relationship['CreatorRelationshipTypeID']),
                        'description' => $relationship['Description'],
                        'date' => '',
                        'startDate' => '',
                        'endDate' => '',
                        'culture' => 'en',
                    ];
                }
            }

        }

        $outputData['authorityRecords'] = $resultingData;

        return $outputData;
    }

    public function exportData($data)
    {

        $header['authorityRecords'] = [
            'culture', 'typeOfEntity', 'authorizedFormOfName',
            'corporateBodyIdentifiers', 'datesOfExistence', 'history', 'places',
            'legalStatus', 'functions', 'mandates', 'internalStructures',
            'generalContext', 'descriptionIdentifier', 'institutionIdentifier',
            'rules', 'status', 'levelOfDetail', 'revisionHistory', 'sources',
            'maintenanceNotes',
       ];

       $header['authorityRecordsAliases'] = [
            'parentAuthorizedFormOfName', 'alternateForm', 'formType', 
            'culture',
       ];

       $header['authorityRecordsRelationships'] = [
            'sourceAuthorizedFormOfName', 'targetAuthorizedFormOfName', 
            'category', 'description', 'date', 'startDate', 'endDate',
            'culture',
       ];

        $writer_authority_records = Writer::createFromPath('/home/vagrant/code/archon2atom/storage/app/data_import/authority_records_import.csv', 'w+');
        $writer_authority_records->insertOne($header['authorityRecords']);
        $writer_authority_records->insertAll($data['authorityRecords']['authorityRecords']); 


        $writer_authority_records_aliases = Writer::createFromPath('/home/vagrant/code/archon2atom/storage/app/data_import/authority_records_aliases_import.csv', 'w+');
        $writer_authority_records_aliases->insertOne($header['authorityRecordsAliases']);
        $writer_authority_records_aliases->insertAll($data['authorityRecords']['authorityRecordsAliases']); 

        $writer_authority_records_relationships = Writer::createFromPath('/home/vagrant/code/archon2atom/storage/app/data_import/authority_records_relationships_import.csv', 'w+');
        $writer_authority_records_relationships->insertOne($header['authorityRecordsRelationships']);
        $writer_authority_records_relationships->insertAll($data['authorityRecords']['authorityRecordsRelationships']); 
    }


    protected function typeOfEntity($entityID)
    {
        switch ($entityID)
        {
            case '22':
                return 'Corporate body';
                break;
            case '20':
                return 'Family';
                break;
            case '23':
                // return 'Name';
                return '';
                break;
            case '19':
                return 'Person';
                break;
            case '21':
                // return 'Unassigned';
                return '';
                break;
            default:
                return '';
                break;
        }
    }


    protected function creatorRelationshipMapping($relationshipID)
    {
        switch ($relationshipID)
        {
            // archon: hierarchical-parent
            case '2':
                return 'is the superior of';
                break;
            // archon: hierarchical-child
            case '3':
                return 'is the subordinate of';
                break;
            // archon: family
            case '6':   
                return 'family';
                break;
            // archon: associative
            case '7': 
                return 'associative';
                break;
            // archon: temporal-earlier
            case '4':
                return 'is the predecessor of';
                break;
            default: 
                return '';
                break;
        }
    }
}
