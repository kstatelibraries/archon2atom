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

        // loop through collection data first generating the top level hierarchy

        foreach ($data['collectionData'] as $record)
        {
            $tmpLocation = '';
            $tmpLocationName = '';
            $tmpFindingAidAuthor = '';
            $tmpProcessingInfo = '';
            $tmpPublicationDate = '';
            $tmpArchivistNote = '';
            $tmpCreators = '';
            $i = 0;

            if(isset($record['Locations']))
            {

                foreach($record['Locations'] as $location)
                {

                    $extentUnitText = (array_key_exists($location['ExtentUnitID'], $data['extentUnits']) ? $data['extentUnits'][$location['ExtentUnitID']]['ExtentUnit'] : 'Undefined');
                    $extentUnitString = ($extentUnitText != 'Undefined' ? $location['Extent'] . ' ' . $extentUnitText : '' );

                    if($i == 0)
                    {
                        if($location['Location'] == 'Annex')
                        {
                            // Range Value = Barcode
                            $tmpLocationName = sprintf('%s, %s', $location['RangeValue'], $location['Content']);
                            $tmpLocation = 'Annex';
                        } else {
                            $tmpLocationName = $location['Content'];
                            $tmpLocation = sprintf('%s:R%s/S%s/Sf%s; %s, %s',
                                $location['Location'], $location['RangeValue'], $location['Section'],$location['Shelf'],$location['Content'], $extentUnitString);
                        }

                    } else {

                        if($location['Location'] == 'Annex')
                        {
                            // Range Value = Barcode
                            $tmpLocationName = sprintf('%s|%s, %s', $tmpLocationName, $location['RangeValue'], $location['Content']);                            
                            $tmpLocation = sprintf('%s|Annex', $tmpLocation);
                        } else {
                            $tmpLocationName = sprintf('%s|%s', $tmpLocationName, $location['Content']);
                            $tmpLocation = sprintf('%s|%s:R%s/S%s/Sf%s; %s, %s',
                                $tmpLocation, $location['Location'], $location['RangeValue'], $location['Section'],$location['Shelf'],$location['Content'], $extentUnitString);
                        }
                    }
                    $i++;
                    $extentUnitText = '';
                    $extentUnitString = '';
                }
            }

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

                        $tmpBiogHistAuthor = ($data['creators'][$creator]['BiogHistAuthor'] == '' ? '' : 'Biography History Author: ' . $data['creators'][$creator]['BiogHistAuthor'] . "\r\n");
                        $tmpBiogHist = ($data['creators'][$creator]['BiogHist'] == '' ? '' : 'Biography History: ' . $data['creators'][$creator]['BiogHist'] . "\r\n");
                        $tmpBiogSources = ($data['creators'][$creator]['Sources'] == '' ? '' : 'Sources: ' . $data['creators'][$creator]['Sources']);
                        $tmpHistory = sprintf("%s%s%s", $tmpBiogHistAuthor, $tmpBiogHist, $tmpBiogSources);
                    } else {
                        $tmpCreators = sprintf('%s|%s',
                            $tmpCreators, $creatorText);

                        $tmpBiogHistAuthor = ($data['creators'][$creator]['BiogHistAuthor'] == '' ? '' : 'Biography History Authory: ' . $data['creators'][$creator]['BiogHistAuthor'] . "\r\n");
                        $tmpBiogHist = ($data['creators'][$creator]['BiogHist'] == '' ? '' : 'Biography History: ' . $data['creators'][$creator]['BiogHist'] . "\r\n");
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


            $tmpFindingAidAuthor = ($record['FindingAidAuthor'] == '' ? '' : 'Finding Aid Author: ' . $record['FindingAidAuthor'] . "\r\n");
            $tmpProcessingInfo = ($record['ProcessingInfo'] == '' ? '' : 'Processing Info: ' . $record['ProcessingInfo'] . "\r\n");
            $tmpPublicationDate = ($record['PublicationDate'] == '' ? '' : 'Publication Date: ' .  Carbon::createFromFormat('Ymd', $record['PublicationDate'], 'UTC')->toDateString());
            $tmpArchivistNote = $tmpFindingAidAuthor . $tmpProcessingInfo . $tmpArchivistNote;

            $resultingData[] = [
                'legacyId' => $record['ID'],
                'parentId' => '',
                'qubitParentSlug' => '',
                'identifier' => '', //$record['ID'],
                'accessionNumber' => '',
                'title' => $record['Title'],
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
                'physicalObjectName' => $tmpLocationName,
                'physicalObjectLocation' => $tmpLocation,
                'physicalObjectType' => 'Box',
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



        // loop through collection Content data ... big copy past of above for the most part.


        foreach ($data['collectionContentData'] as $record)
        {
            $tmpLocation = '';
            $tmpCreators = '';
            $i = 0;

            $parentID = ($record['ParentID'] == 0 ? $record['CollectionID'] : 'cc-' . $record['ParentID']);
            $recordID = 'cc-' . $record['ID'];


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

                        $tmpBiogHistAuthor = ($data['creators'][$creator]['BiogHistAuthor'] == '' ? '' : 'Biography History Author: ' . $data['creators'][$creator]['BiogHistAuthor'] . "\r\n");
                        $tmpBiogHist = ($data['creators'][$creator]['BiogHist'] == '' ? '' : 'Biography History: ' . $data['creators'][$creator]['BiogHist'] . "\r\n");
                        $tmpBiogSources = ($data['creators'][$creator]['Sources'] == '' ? '' : 'Sources: ' . $data['creators'][$creator]['Sources']);
                        $tmpHistory = sprintf("%s%s%s", $tmpBiogHistAuthor, $tmpBiogHist, $tmpBiogSources);
                    } else {
                        $tmpCreators = sprintf('%s|%s', 
                            $tmpCreators, $creatorText);

                        $tmpBiogHistAuthor = ($data['creators'][$creator]['BiogHistAuthor'] == '' ? '' : 'Biography History Authory: ' . $data['creators'][$creator]['BiogHistAuthor'] . "\r\n");
                        $tmpBiogHist = ($data['creators'][$creator]['BiogHist'] == '' ? '' : 'Biography History: ' . $data['creators'][$creator]['BiogHist'] . "\r\n");
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

            $eadLevel = $this->levelsOfDescription($record['EADLevel']);

            $resultingData[] = [
                'legacyId' => $recordID,
                'parentId' => $parentID,
                'qubitParentSlug' => '',
                'identifier' => '', // Do not have a unique ID ...
                'accessionNumber' => '', // Does not have one
                'title' => ($record['Title'] == '' ? $record['UniqueID'] : $record['Title']),
                'levelOfDescription' => ($eadLevel != '' ? $eadLevel : (strpos($record['UniqueID'], 'Box') !== false ? 'Box' : '')),
                'extentAndMedium' => '', // N/A
                'repository' => 'Morse Department of Special Collections',
                'archivalHistory' => '', // N/A
                'acquisition' => '', // N/A
                'scopeAndContent' => '', // N/A
                'appraisal' => '', // N/A
                'accruals' => '', // N/A
                'arrangement' => '', // N/A
                'accessConditions' => '', // N/A
                'reproductionConditions' => '', // N/A
                'language' => '',
                'script' => '',
                'languageNote' => '',
                'physicalCharacteristics' => '',
                'findingAids' => '', // N/A
                'locationOfOriginals' => '', // N/A
                'locationOfCopies' => '', // N/A
                'relatedUnitsOfDescription' => '', // N/A
                'publicationNote' => '', // N/A
                'digitalObjectURI' => '', // N/A
                'generalNote' => $record['Description'],
                'subjectAccessPoints' => $tmpSubjects,
                'placeAccessPoints' => '',
                'nameAccessPoints' => '',
                'genreAccessPoints' => '',
                'descriptionIdentifier' => '',
                'institutionIdentifier' => '',
                'rules' => '', // N/A
                'descriptionStatus' => '',
                'levelOfDetail' => '',
                'revisionHistory' => '', // N/A
                'languageOfDescription' => 'en',
                'scriptOfDescription' => '',
                'sources' => '', // N/A
                'archivistNote' => '', // N/A
                'publicationStatus' => ($record['Enabled'] == 0 ? 'Draft' : 'Published'),
                'physicalObjectName' => '',
                'physicalObjectLocation' => '',
                'physicalObjectType' => '',
                'alternativeIdentifiers' => '',
                'alternativeIdentifierLabels' => '',
                'eventDates' => $record['Date'],
                'eventTypes' => '',
                'eventStartDates' => '', // N/A
                'eventEndDates' => '', // N/A
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


        // Export Collection Level Data
        $collections = collect($data['infoObjects'])->where("levelOfDescription", 'Collection')->toArray();
        $series = collect($data['infoObjects'])->where("levelOfDescription", 'Series')->toArray();
        $subseries = collect($data['infoObjects'])->where("levelOfDescription", 'Subseries')->toArray();
        $box = collect($data['infoObjects'])->where("levelOfDescription", 'Box')->toArray();
        $file = collect($data['infoObjects'])->where("levelOfDescription", 'File')->toArray();
        $item = collect($data['infoObjects'])->where("levelOfDescription", 'Item')->toArray();
        $blank = $subseries = collect($data['infoObjects'])->where("levelOfDescription", '')->toArray();

        $writer_collections = Writer::createFromPath('/home/vagrant/code/archon2atom/storage/app/data_import/information_objects_import-collections.csv', 'w+');
        $writer_collections->insertOne($header['infoObjects']);
        $writer_collections->insertAll($collections); 

        $writer_series = Writer::createFromPath('/home/vagrant/code/archon2atom/storage/app/data_import/information_objects_import-series.csv', 'w+');
        $writer_series->insertOne($header['infoObjects']);
        $writer_series->insertAll($series); 

        $writer_subseries = Writer::createFromPath('/home/vagrant/code/archon2atom/storage/app/data_import/information_objects_import-subseries.csv', 'w+');
        $writer_subseries->insertOne($header['infoObjects']);
        $writer_subseries->insertAll($subseries); 

        $writer_box = Writer::createFromPath('/home/vagrant/code/archon2atom/storage/app/data_import/information_objects_import-box.csv', 'w+');
        $writer_box->insertOne($header['infoObjects']);
        $writer_box->insertAll($box); 

        $writer_file = Writer::createFromPath('/home/vagrant/code/archon2atom/storage/app/data_import/information_objects_import-file.csv', 'w+');
        $writer_file->insertOne($header['infoObjects']);
        $writer_file->insertAll($file); 

        $writer_item = Writer::createFromPath('/home/vagrant/code/archon2atom/storage/app/data_import/information_objects_import-item.csv', 'w+');
        $writer_item->insertOne($header['infoObjects']);
        $writer_item->insertAll($item); 

        $writer_blank = Writer::createFromPath('/home/vagrant/code/archon2atom/storage/app/data_import/information_objects_import-blank.csv', 'w+');
        $writer_blank->insertOne($header['infoObjects']);
        $writer_blank->insertAll($blank); 
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

    protected function levelsOfDescription($level)
    {
        switch(strtolower($level))
        {
            case 'file':
                return 'File';
                break;
            case 'item':
                return 'Item';
                break;
            case 'series':
                return 'Series';
                break;
            case 'subseries':
                return 'Subseries';
                break;
            default:
                return '';
                break;
        }
    }
}
