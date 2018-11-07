<?php

namespace App\Archon2Atom;

use League\Csv\Writer;
use Carbon\Carbon;

class AtomInformationObjects
{

    public function __construct($rawData)
    {
        $resultingData = $this->processData($rawData);
        $this->exportData($resultingData);
    }

    public function processData($data)
    {

        foreach ($data['collectionData'] as $record)
        {
            $tmpLocation = '';
            $tmpFindingAidAuthor = '';
            $tmpProcessingInfo = '';
            $tmpPublicationDate = '';
            $tmpArchivistNote = '';
            $tmpCreators = '';
            $title = '';
            $i = 0;

            // if(isset($record['Locations']))
            // {

            //     foreach($record['Locations'] as $location)
            //     {

            //         $extentUnitText = (array_key_exists($location['ExtentUnitID'], $data['extentUnits']) ? $data['extentUnits'][$location['ExtentUnitID']]['ExtentUnit'] : 'Undefined'); 
            //         $extentUnitString = ($extentUnitText != 'Undefined' ? $location['Extent'] . ' ' . $extentUnitText : '' );

            //         if($i == 0)
            //         {    
            //             $tmpLocation = sprintf('%s, %s; %s:R%s/S%s/Sf%s',
            //                 $location['Content'], $extentUnitString, $location['Location'], $location['RangeValue'], $location['Section'],$location['Shelf']);
            //         } else {
            //             $tmpLocation = sprintf('%s|%s, %s; %s:R%s/S%s/Sf%s',
            //                 $tmpLocation, $location['Content'], $extentUnitString, $location['Location'], $location['RangeValue'], $location['Section'],$location['Shelf']);
            //         }
            //         $i++;
            //         $extentUnitText = '';
            //         $extentUnitString = '';
            //     }
            // }

            // $tmpDate = '';

            $tmpBiogHistAuthor = '';
            $tmpBiogHist = '';
            $tmpBiogSources = '';
            $tmpHistory = '';

            $j = 0;
            if(isset($record['Creators']))
            {

                foreach($record['Creators'] as $creator)
                {
                    $tmpBiogHistAuthor = '';
                    $tmpBiogHist = '';
                    $tmpBiogSources = '';

                    $creatorText = (array_key_exists($creator, $data['creators']) ? $data['creators'][$creator]['Name'] : ''); 
                    if($j == 0)
                    {    
                        $tmpCreators = sprintf('%s', $creatorText);

                        $tmpBiogHistAuthor = ($data['creators'][$creator]['BiogHistAuthor'] == '' ? '' : 'Biography History Author: ' . $data['creators'][$creator]['BiogHistAuthor'] . '\n');
                        $tmpBiogHist = ($data['creators'][$creator]['BiogHist'] == '' ? '' : 'Biography History: ' . $data['creators'][$creator]['BiogHist'] . '\n');
                        $tmpBiogSources = ($data['creators'][$creator]['Sources'] == '' ? '' : 'Sources: ' . $data['creators'][$creator]['Sources']);
                        $tmpHistory = sprintf("%s%s%s", $tmpBiogHistAuthor, $tmpBiogHist, $tmpBiogSources);
                    } else {
                        $tmpCreators = sprintf('%s|%s', 
                            $tmpCreators, $creatorText);

                        $tmpBiogHistAuthor = ($data['creators'][$creator]['BiogHistAuthor'] == '' ? '' : 'Biography History Authory: ' . $data['creators'][$creator]['BiogHistAuthor'] . '\n');
                        $tmpBiogHist = ($data['creators'][$creator]['BiogHist'] == '' ? '' : 'Biography History: ' . $data['creators'][$creator]['BiogHist'] . '\n');
                        $tmpBiogSources = ($data['creators'][$creator]['Sources'] == '' ? '' : 'Sources: ' . $data['creators'][$creator]['Sources']);

                        $tmpHistory = sprintf("%s|%s%s%s", $tmpHistory, $tmpBiogHistAuthor, $tmpBiogHist, $tmpBiogSources);
                    }
                    $j++;
                    $creatorText = '';
                }
            }

            $k = 0;
            $subjectText = '';
            $tmpSubjects = '';
            if(isset($record['Subjects']))
            {

                foreach($record['Subjects'] as $subject)
                {

                    $subjectText = (array_key_exists($subject, $data['subjects']) ? $data['subjects'][$subject]['Subject'] : ''); 
                    if($k == 0)
                    {    
                        $tmpSubjects = sprintf('%s', $subjectText);

                    } else {
                        $tmpSubjects = sprintf('%s|%s', 
                            $tmpSubjects, $subjectText);
                    }
                    $k++;
                    $subjectText = '';
                }
            }


            $tmpFindingAidAuthor = ($record['FindingAidAuthor'] == '' ? '' : 'Finding Aid Author: ' . $record['FindingAidAuthor'] . '\n');
            $tmpProcessingInfo = ($record['ProcessingInfo'] == '' ? '' : 'Processing Info: ' . $record['ProcessingInfo'] . '\n');
            $tmpPublicationDate = ($record['PublicationDate'] == '' ? '' : 'Publication Date: ' .  Carbon::createFromFormat('Ymd', $record['PublicationDate'], 'UTC')->toDateString());
            $tmpArchivistNote = $tmpFindingAidAuthor . $tmpProcessingInfo . $tmpArchivistNote;
            $title = explode(", ", $record['Title'], 2);

            $resultingData[] = [
                'legacyId' => $record['ID'],
                'parentId' => '',
                'qubitParentSlug' => '',
                'identifier' => '', //$record['ID'],
                'accessionNumber' => '',
                'title' => (count($title) > 1 ? $title[1] : $record['Title']),
                'levelOfDescription' => 'Collection',
                'extentAndMedium' => (array_key_exists($record['ExtentUnitID'], $data['extentUnits']) ? $record['Extent'] . ' ' . $data['extentUnits'][$record['ExtentUnitID']]['ExtentUnit'] : ''),
                'repository' => 'Morse Department of Special Collections',
                'archivalHistory' => $record['CustodialHistory'],
                'acquisition' => $record['AcquisitionSource'],
                'scopeAndContent' => $record['Scope'],
                'appraisal' => $record['AppraisalInfo'],
                'accruals' => $record['AccrualInfo'],
                'arrangement' => $record['Arrangement'],
                'accessConditions' => $record['AccessRestrictions'],
                'reproductionConditions' => $record['UseRestrictions'],
                'language' => '',
                'script' => '',
                'languageNote' => '',
                'physicalCharacteristics' => '',
                'findingAids' => $record['PublicationNote'],
                'locationOfOriginals' => '' , // blank in our data
                'locationOfCopies' => '', // blank in our data
                'relatedUnitsOfDescription' => $record['RelatedMaterials'],
                'publicationNote' => $record['PublicationNote'],
                'digitalObjectURI' => '',
                'generalNote' => $record['OtherNote'],
                'subjectAccessPoints' => $tmpSubjects, // need to build
                'placeAccessPoints' => '',
                'nameAccessPoints' => '',
                'genreAccessPoints' => '',
                'descriptionIdentifier' => '',
                'institutionIdentifier' => '',
                'rules' => $this->descriptiveRules($record['DescriptiveRulesID']), 
                'descriptionStatus' => '',
                'levelOfDetail' => '',
                'revisionHistory' => $record['RevisionHistory'],
                'languageOfDescription' => 'en',
                'scriptOfDescription' => '',
                'sources' => $record['PublicationNote'], 
                'archivistNote' => $tmpArchivistNote, 
                'publicationStatus' => ($record['Enabled'] == 0 ? 'Draft' : 'Published'),
                'physicalObjectName' => '',
                'physicalObjectLocation' => '',
                'physicalObjectType' => '',
                'alternativeIdentifiers' => '',
                'alternativeIdentifierLabels' => '',
                'eventDates' => $record['InclusiveDates'],
                'eventTypes' => '',
                'eventStartDates' => $record['NormalDateBegin'],
                'eventEndDates' => $record['NormalDateEnd'],
                'eventActors' => $tmpCreators,
                'eventActorHistories' => $tmpHistory,
                'culture' => 'en',
            ];

        }

        $outputData['infoObjects'] = $resultingData;

        return $outputData;
    }

    public function exportData($data)
    {

        $header['infoObjects'] = [
            'legacyId', 'parentId', 'qubitParentSlug', 'identifier', 
            'accessionNumber', 'title', 'levelOfDescription', 'extentAndMedium', 
            'repository', 'archivalHistory', 'acquisition', 'scopeAndContent', 
            'appraisal', 'accruals', 'arrangement', 'accessConditions', 
            'reproductionConditions', 'language', 'script', 'languageNote', 
            'physicalCharacteristics', 'findingAids', 'locationOfOriginals', 
            'locationOfCopies', 'relatedUnitsOfDescription', 'publicationNote', 
            'digitalObjectURI', 'generalNote', 'subjectAccessPoints', 
            'placeAccessPoints', 'nameAccessPoints', 'genreAccessPoints', 
            'descriptionIdentifier', 'institutionIdentifier', 'rules', 
            'descriptionStatus', 'levelOfDetail', 'revisionHistory', 
            'languageOfDescription', 'scriptOfDescription', 'sources', 
            'archivistNote', 'publicationStatus', 'physicalObjectName', 
            'physicalObjectLocation', 'physicalObjectType', 
            'alternativeIdentifiers', 'alternativeIdentifierLabels', 
            'eventDates', 'eventTypes', 'eventStartDates', 'eventEndDates',
            'eventActors', 'eventActorHistories', 'culture',
            ];


        $writer_users = Writer::createFromPath('/home/vagrant/code/archon2atom/storage/app/data_import/information_objects_import.csv', 'w+');
        $writer_users->insertOne($header['infoObjects']);
        $writer_users->insertAll($data['infoObjects']); 
    }

    protected function descriptiveRules($ruleID)
    {

        switch($ruleID)
        {
            case 1:
                return 'Describing Archives: A Content Standard';
                break;
            case 2:
                return 'Anglo-American Cataloging Rules, 2nd Edition';
                break;
            case 3:
                return 'Rules for Archival Description';
                break;
            case 4:
                return 'General International Standard for Archival Description';
                break;                
            default:
                return '';
                break;                                
        }
    }
}
