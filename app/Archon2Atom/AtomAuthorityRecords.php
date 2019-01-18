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

        foreach ($data['creatorData'] as $creator) {
            // print_r($creator);
           
            $creatorSource = ($creator['CreatorSourceID'] == ''
                ? ''
                : 'Creator Source: ' . $data['creatorSourceData'][$creator['CreatorSourceID']]['CreatorSource']
            );

            $biogHistAuthor = ($creator['BiogHistAuthor'] == ''
                ? ''
                : 'Biographical/Historical Note Author: ' . $creator['BiogHistAuthor']
            );

            $creatorSourceBiogAuthorSeperator = ($creatorSource !== '' && $biogHistAuthor !== '' ? "\r\n" : '');

            $resultingData['authorityRecords'][] = [
                'culture' => 'en',
                'typeOfEntity' => $this->typeOfEntity($creator['CreatorTypeID']),
                'authorizedFormOfName' => $creator['Name'],
                'corporateBodyIdentifiers' => '',
                'datesOfExistence' => $creator['Dates'],
                'history' => str_replace("\n", "\r\n", $creator['BiogHist']),
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

            if ($creator['NameFullerForm'] !== '') {
                $resultingData['authorityRecordsAliases'][] = [
                    'parentAuthorizedFormOfName' => $creator['Name'],
                    'alternateForm' => $creator['NameFullerForm'],
                    'formType' => 'other',
                    'culture' => 'en',
                ];
            }

            if ($creator['NameVariants'] !== '') {
                $nameVariants = explode(';', $creator['NameVariants']);


                foreach ($nameVariants as $name) {
                    $resultingData['aliases'][] = [
                        'parentAuthorizedFormOfName' => $creator['Name'],
                        'alternateForm' => trim($name),
                        'formType' => 'other',
                        'culture' => 'en',
                    ];
                }
            }


            if (isset($creator['CreatorRelationships'])) {
                foreach ($creator['CreatorRelationships'] as $relationship) {
                    $relationships['authorityRecordsRelationships'][] = [
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

            $collectionRelationships = collect($relationships)->collapse();

            $protectedIds = [];
        foreach ($collectionRelationships as $id => $relationship) {
            foreach ($collectionRelationships as $matchId => $match) {
                $remove = false;

                if ($relationship['sourceAuthorizedFormOfName'] == $match['targetAuthorizedFormOfName']
                    && $relationship['targetAuthorizedFormOfName'] == $match['sourceAuthorizedFormOfName']) {
                    $remove = false;

                    // is the superior of   is the subordinate of
                    if ($relationship['category'] == 'is the superior of'
                        && $match['category'] == 'is the subordinate of') {
                        $remove = true;
                    }

                    // is the subordinate of   is the superior of
                    if ($relationship['category'] == 'is the subordinate of'
                        && $match['category'] == 'is the superior of') {
                        // $remove = true;
                    }

                    // family  is the superior of
                    if ($relationship['category'] == 'family'
                        && $match['category'] == 'is the superior of') {
                        // do not remove, unique relationship ...
                        // $remove = true;
                    }

                    // family  family
                    if ($relationship['category'] == 'family'
                        && $match['category'] == 'family') {
                        $remove = true;
                    }

                    // is the superior of  family
                    if ($relationship['category'] == 'is the superior of'
                        && $match['category'] == 'family') {
                        // do not remove, unique relationship ...
                        // $remove = true;
                    }

                    // associative associative
                    if ($relationship['category'] == 'associative'
                        && $match['category'] == 'associative') {
                        $remove = true;
                    }

                    if ($remove && !in_array($matchId, $protectedIds)) {
                        $protectedIds[] = $id;
                        $collectionRelationships->forget($matchId);
                    }
                }
            }
        }

        $resultingData['relationships'] = $collectionRelationships->toArray();
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

        $header['aliases'] = [
            'parentAuthorizedFormOfName', 'alternateForm', 'formType',
            'culture',
        ];

        $header['relationships'] = [
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
        switch ($entityID) {
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
        switch ($relationshipID) {
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
