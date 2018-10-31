<?php

namespace App\Archon2Atom;

use League\Csv\Writer;
use Carbon\Carbon;

class AtomAccessions
{

    public function __construct($rawData)
    {
        $resultingData = $this->processData($rawData);
        $this->exportData($resultingData);
    }

    public function processData($data)
    {

        foreach ($data['accessionData'] as $record)
        {
            $tmpLocation = '';
            $i = 0;
            if(isset($record['Locations']))
            {

                foreach($record['Locations'] as $location)
                {

                    $extentUnitText = (array_key_exists($location['ExtentUnitID'], $data['extentUnits']) ? $data['extentUnits'][$location['ExtentUnitID']]['ExtentUnit'] : 'Undefined'); 
                    if($i == 0)
                    {    
                        $tmpLocation = sprintf('Location: %s Content: %s RangeValue: %s Section: %s Shelf: %s Extent: %s [%s] DisplayPosition: %s', 
                            $location['Location'], $location['Content'], $location['RangeValue'], $location['Section'],$location['Shelf'], $location['Extent'], $extentUnitText, $location['DisplayPosition']);
                    } else {
                        $tmpLocation = sprintf('%s|Location: %s Content: %s RangeValue: %s Section: %s Shelf: %s Extent: %s [%s] DisplayPosition: %s', 
                            $tmpLocation, $location['Location'], $location['Content'], $location['RangeValue'], $location['Section'],$location['Shelf'], $location['Extent'], $extentUnitText, $location['DisplayPosition']);
                    }
                    $i++;
                }
            }

            $tmpCreators = '';
            $j = 0;
            if(isset($record['Creators']))
            {

                foreach($record['Creators'] as $creator)
                {

                    $creatorText = (array_key_exists($creator, $data['creators']) ? $data['creators'][$creator]['Name'] : 'Undefined'); 
                    if($j == 0)
                    {    
                        $tmpCreators = sprintf('%s', $creatorText);
                    } else {
                        $tmpCreators = sprintf('%s|%s', 
                            $tmpCreators, $creatorText);
                    }
                    $j++;
                }
            }

            $title = explode(", ", $record['Title'], 2);

            $tmpDate = str_replace("–", "-", $record['InclusiveDates']);
            $eventDates = explode("-", $tmpDate, 2);
            $eventStartDate = (count($eventDates) > 1 ? $eventDates[0] : $record['InclusiveDates']);
            $eventEndDate = (count($eventDates) > 1 ? $eventDates[1] : '');

            $resultingData[] = [
                'accessionNumber' => $record['Identifier'],
                'acquisitionDate' => Carbon::createFromFormat('Ymd', $record['AccessionDate'], 'UTC')->toDateString(),
                'sourceOfAcquisition' => $record['Donor'],
                'locationInformation' => $tmpLocation,
                'acquisitionType' => $record['DonorNotes'],
                'resourceType' => '',
                'title' => (count($title) > 1 ? $title[1] : $record['Title']),
                'archivalHistory' => $record['DonorNotes'],
                'scopeAndContent' => $record['ScopeContent'],
                'appraisal' => $record['Comments'],
                'physicalCondition' => $record['PhysicalDescription'],
                'receivedExtentUnits' => $record['ReceivedExtent'] . (array_key_exists($record['ReceivedExtentUnitID'], $data['extentUnits']) ? ' ' . $data['extentUnits'][$record['ReceivedExtentUnitID']]['ExtentUnit'] : ''),
                'processingStatus' => '',
                'processingPriority' => '',
                'processingNotes' => $record['Comments'] . (array_key_exists($record['MaterialTypeID'], $data['materialTypes']) ? ' Material Type: ' . $data['materialTypes'][$record['MaterialTypeID']]['MaterialType'] : ''),
                'donorName' => $record['Donor'],
                'donorStreetAddress' => $record['DonorContactInformation'],
                'donorCity' => $record['DonorContactInformation'],
                'donorRegion' => $record['DonorContactInformation'],
                'donorCountry' => $record['DonorContactInformation'],
                'donorPostalCode' => $record['DonorContactInformation'],
                'donorTelephone' => $record['DonorContactInformation'],
                'donorEmail' => $record['DonorContactInformation'],
                'creators' => $tmpCreators,
                'eventTypes' => '',
                'eventDates' => $record['InclusiveDates'],
                'eventStartDates' => $eventStartDate,
                'eventEndDates' => $eventEndDate,
                'culture' => '',
            ];

        }

        $outputData['accessions'] = $resultingData;

        return $outputData;
    }

    public function exportData($data)
    {

        $header['accessions'] = [
            'accessionNumber','acquisitionDate','sourceOfAcquisition','locationInformation','acquisitionType','resourceType','title','archivalHistory','scopeAndContent','appraisal','physicalCondition','receivedExtentUnits','processingStatus','processingPriority','processingNotes','donorName','donorStreetAddress','donorCity','donorRegion','donorCountry','donorPostalCode','donorTelephone','donorEmail','creators','eventTypes','eventDates','eventStartDates','eventEndDates','culture'
            ];

        $writer_users = Writer::createFromPath('/home/vagrant/code/archon2atom/storage/app/data_import/accessions_import.csv', 'w+');
        $writer_users->insertOne($header['accessions']);
        $writer_users->insertAll($data['accessions']); 
    }
}
