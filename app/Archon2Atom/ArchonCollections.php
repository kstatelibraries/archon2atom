<?php

namespace App\Archon2Atom;

use League\Csv\Writer;

class ArchonCollections
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
                    'Abstract' => $record['Abstract'],
                    'AccessRestrictions' => $record['AccessRestrictions'],
                    'AccrualInfo' => $record['AccrualInfo'],
                    'AcquisitionDate' => $record['AcquisitionDate'],
                    'AcquisitionMethod' => $record['AcquisitionMethod'],
                    'AcquisitionSource' => $record['AcquisitionSource'],
                    'AltExtentStatement' => $record['AltExtentStatement'],
                    'AppraisalInfo' => $record['AppraisalInfo'],
                    'Arrangement' => $record['Arrangement'],
                    'BiogHist' => $record['BiogHist'],
                    'BiogHistAuthor' => $record['BiogHistAuthor'],
                    'ClassificationID' => $record['ClassificationID'],
                    'CollectionIdentifier' => $record['CollectionIdentifier'],
                    'CustodialHistory' => $record['CustodialHistory'],
                    'DescriptiveRulesID' => $record['DescriptiveRulesID'],
                    'Enabled' => $record['Enabled'],
                    'ExtentUnitID' => $record['ExtentUnitID'],
                    'Extent' => $record['Extent'],
                    'FindingAidAuthor' => $record['FindingAidAuthor'],
                    'FindingLanguageID' => $record['FindingLanguageID'],
                    'ID' => $record['ID'],
                    'InclusiveDates' => $record['InclusiveDates'],
                    'MaterialTypeID' => $record['MaterialTypeID'],
                    'NormalDateBegin' => $record['NormalDateBegin'],
                    'NormalDateEnd' => $record['NormalDateEnd'],
                    'OrigCopiesNote' => $record['OrigCopiesNote'],
                    'OrigCopiesURL' => $record['OrigCopiesURL'],
                    'OtherNote' => $record['OtherNote'],
                    'OtherURL' => $record['OtherURL'],
                    'PhysicalAccess' => $record['PhysicalAccess'],
                    'PredominantDates' => $record['PredominantDates'],
                    'PreferredCitation' => $record['PreferredCitation'],
                    'PrimaryCreator' => $record['PrimaryCreator'],
                    'ProcessingInfo' => $record['ProcessingInfo'],
                    'PublicationDate' => $record['PublicationDate'],
                    'PublicationNote' => $record['PublicationNote'],
                    'RelatedMaterials' => $record['RelatedMaterials'],
                    'RelatedMaterialsURL' => $record['RelatedMaterialsURL'],
                    'RelatedPublications' => $record['RelatedPublications'],
                    'RepositoryID' => $record['RepositoryID'],
                    'RevisionHistory' => $record['RevisionHistory'],
                    'Scope' => $record['Scope'],
                    'SeparatedMaterials' => $record['SeparatedMaterials'],
                    'SortTitle' => $record['SortTitle'],
                    'TechnicalAccess' => $record['TechnicalAccess'],
                    'Title' => $record['Title'],
                    'UseRestrictions' => $record['UseRestrictions'],
                ];

                foreach ($record['Creators'] as $creator) {
                    $creators[] =
                    [
                        'collectionID' => $record['ID'],
                        'creatorID' => $creator,
                    ];
                }

                if (isset($record['Locations'])) {
                    foreach ($record['Locations'] as $location) {
                        $locationData[] =
                        [
                            'collectionID' => $record['ID'],
                            'Location' => $location['Location'],
                            'Content' => $location['Content'],
                            'RangeValue' => $location['RangeValue'],
                            'Section' => $location['Section'],
                            'Shelf' => $location['Shelf'],
                            'Extent' => $location['Extent'],
                            'ExtentUnitID' => $location['ExtentUnitID'],
                            'DisplayPosition' => $location['DisplayPosition'],
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

        $outputData['collections'] = $resultingData;
        $outputData['locations'] = $locationData;
        $outputData['creators'] = $creators;
        $outputData['subjects'] = $subjectData;

        return $outputData;
    }

    public function exportData($data)
    {

        $header['collections'] = [
            'Abstract', 'AccessRestrictions', 'AccrualInfo', 'AcquisitionDate',
            'AcquisitionMethod', 'AcquisitionSource', 'AltExtentStatement',
            'AppraisalInfo', 'Arrangement', 'BiogHist', 'BiogHistAuthor',
            'ClassificationID', 'CollectionIdentifier', 'CustodialHistory',
            'DescriptiveRulesID', 'Enabled', 'ExtentUnitID', 'Extent',
            'FindingAidAuthor', 'FindingLanguageID', 'ID', 'InclusiveDates',
            'MaterialTypeID', 'NormalDateBegin', 'NormalDateEnd',
            'OrigCopiesNote', 'OrigCopiesURL', 'OtherNote', 'OtherURL',
            'PhysicalAccess', 'PredominantDates', 'PreferredCitation',
            'PrimaryCreator', 'ProcessingInfo', 'PublicationDate',
            'PublicationNote', 'RelatedMaterials', 'RelatedMaterialsURL',
            'RelatedPublications', 'RepositoryID', 'RevisionHistory', 'Scope',
            'SeparatedMaterials', 'SortTitle', 'TechnicalAccess', 'Title',
            'UseRestrictions',
            ];
        $header['subjects'] = [
            'collectionID', 'subjectID'
        ];
        $header['locations'] = [
            'collectionID', 'Location', 'Content', 'RangeValue', 'Section',
            'Shelf', 'Extent', 'ExtentUnitID', 'DisplayPosition'
        ];
        $header['creators'] = [
            'collectionID', 'creatorID'
        ];

        $dataSet = [
            'collections',
            'subjects',
            'locations',
            'creators',
        ];

        foreach ($dataSet as $item) {
            $name = ($item == 'collections' ? $item : 'collections-' . $item);
            $writer = Writer::createFromPath(
                '/home/vagrant/code/archon2atom/storage/app/data_export/' .
                $name . '.csv',
                'w+'
            );
            $writer->insertOne($header[$item]);
            $writer->insertAll($data[$item]);
        }
    }
}
