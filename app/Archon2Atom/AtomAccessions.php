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

        foreach ($data['accessionData'] as $record) {
            $tmpLocation = '';
            $i = 0;
            if (isset($record['Locations'])) {
                foreach ($record['Locations'] as $location) {
                    $extentUnitText = (array_key_exists($location['ExtentUnitID'], $data['extentUnits'])
                        ? $data['extentUnits'][$location['ExtentUnitID']]['ExtentUnit']
                        : 'Undefined'
                    );
                    $extentUnitString = ($extentUnitText != 'Undefined'
                        ? $location['Extent'] . ' ' . $extentUnitText
                        : ''
                    );

                    if ($i == 0) {
                        $tmpLocation = sprintf(
                            '%s, %s; %s:R%s/S%s/Sf%s',
                            $location['Content'],
                            $extentUnitString,
                            $location['Location'],
                            $location['RangeValue'],
                            $location['Section'],
                            $location['Shelf']
                        );
                    } else {
                        $tmpLocation = sprintf(
                            "%s\r\n%s, %s; %s:R%s/S%s/Sf%s",
                            $tmpLocation,
                            $location['Content'],
                            $extentUnitString,
                            $location['Location'],
                            $location['RangeValue'],
                            $location['Section'],
                            $location['Shelf']
                        );
                    }
                    $i++;
                    $extentUnitText = '';
                    $extentUnitString = '';
                }
            }

            $tmpCreators = '';
            $tmpDate = '';
            $title = '';
            $j = 0;
            if (isset($record['Creators'])) {
                foreach ($record['Creators'] as $creator) {
                    $creatorText = (array_key_exists($creator, $data['creators'])
                        ? $data['creators'][$creator]['Name']
                        : ''
                    );
                    if ($j == 0) {
                        $tmpCreators = sprintf('%s', $creatorText);
                    } else {
                        $tmpCreators = sprintf(
                            '%s|%s',
                            $tmpCreators,
                            $creatorText
                        );
                    }
                    $j++;
                    $creatorText = '';
                }
            }

            $title = explode(", ", $record['Title'], 2);

            $tmpDate = $record['InclusiveDates'];
            $tmpDate = str_replace("–", "-", $tmpDate);
            $tmpDate = preg_replace('/[;,&\/]/','|',$tmpDate);
            $eventDates = explode("|", $tmpDate);

            $eventStartDate = '';
            $eventEndDate = '';

            if (count($eventDates) == 1) {
                $eventDates = explode("-", $eventDates[0]);
                $eventStartDate = preg_replace('/[^0-9]/', '', $eventDates[0]);
                $eventEndDate = (array_key_exists(1, $eventDates)
                    ? preg_replace('/[^0-9]/', '', $eventDates[1])
                    : ''
                );
            } elseif ($tmpDate == "|") {
                $eventStartDate = '';
                $eventEndDate = '';
            } else {
                $k = 0;
                $tmpStartDate = '';
                $tmpEndDate = '';
                foreach ($eventDates as $event) {
                    $eventDates = explode("-", $event);
                    $tmpStartDate = preg_replace('/[^0-9]/', '', $eventDates[0]);
                    // $tmpStartDate = preg_replace('/([^\d]+)(?:[0-9]{4})/', '', $eventDates[0]);
                    $tmpEndDate = (array_key_exists(1, $eventDates)
                        ? preg_replace('/[^0-9]/', '', $eventDates[1])
                        : ''
                    );

                    if ($k == 0) {
                        $eventStartDate = sprintf("%s", $tmpStartDate);
                        $eventEndDate = sprintf("%s", $tmpEndDate);
                    } else {
                        $eventStartDate = sprintf("%s|%s", $eventStartDate, $tmpStartDate);
                        $eventEndDate = sprintf("%s|%s", $eventEndDate, $tmpEndDate);
                    }
                    $k++;
                }
            }
            $resultingData[] = [
                'accessionNumber' => $record['Identifier'],
                'acquisitionDate' => Carbon::createFromFormat('Ymd', $record['AccessionDate'], 'UTC')->toDateString(),
                'sourceOfAcquisition' => $record['Donor'],
                'locationInformation' => $tmpLocation,
                'acquisitionType' => '',
                'resourceType' => '',
                'title' => (count($title) > 1 ? $title[1] : $record['Title']),
                'archivalHistory' => $record['DonorNotes'] . ($record['DonorContactInformation'] != ''
                    ? ($record['DonorNotes'] != ''
                        ? '|'
                        : '') . $record['DonorContactInformation']
                    : ''
                ),
                'scopeAndContent' => $record['ScopeContent'],
                'appraisal' => $record['Comments'],
                'physicalCondition' => $record['PhysicalDescription'],
                'receivedExtentUnits' => (array_key_exists($record['ReceivedExtentUnitID'], $data['extentUnits'])
                    ? $record['ReceivedExtent'] . ' '
                        . $data['extentUnits'][$record['ReceivedExtentUnitID']]['ExtentUnit']
                    : ''
                ),
                'processingStatus' => '',
                'processingPriority' => '',
                'processingNotes' => $record['Comments']
                    . (array_key_exists($record['MaterialTypeID'], $data['materialTypes'])
                    ? ' Material Type: ' . $data['materialTypes'][$record['MaterialTypeID']]['MaterialType']
                    : ''
                ),
                'donorName' => $record['Donor'],
                'donorStreetAddress' => '',
                'donorCity' => '',
                'donorRegion' => '',
                'donorCountry' => '',
                'donorPostalCode' => '',
                'donorTelephone' => '',
                'donorEmail' => '',
                'creators' => $tmpCreators,
                'eventTypes' => '',
                'eventDates' => $tmpDate,
                'eventStartDates' => $eventStartDate,
                'eventEndDates' => $eventEndDate,
                'culture' => 'en',
            ];
        }

        $outputData['accessions'] = $resultingData;

        return $outputData;
    }

    public function exportData($data)
    {

        $header['accessions'] = [
            'accessionNumber', 'acquisitionDate', 'sourceOfAcquisition',
            'locationInformation', 'acquisitionType', 'resourceType', 'title',
            'archivalHistory', 'scopeAndContent', 'appraisal',
            'physicalCondition', 'receivedExtentUnits', 'processingStatus',
            'processingPriority', 'processingNotes', 'donorName',
            'donorStreetAddress', 'donorCity', 'donorRegion', 'donorCountry',
            'donorPostalCode', 'donorTelephone', 'donorEmail', 'creators',
            'eventTypes', 'eventDates', 'eventStartDates', 'eventEndDates',
            'culture'
        ];

        $path = '/home/vagrant/code/archon2atom/storage/app/data_import/';
        $writer_users = Writer::createFromPath($path . 'accessions_import.csv', 'w+');
        $writer_users->insertOne($header['accessions']);
        $writer_users->insertAll($data['accessions']);
    }
}
