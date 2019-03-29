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
            $tmpDate = preg_replace('/[;,&\/]/', '|', $tmpDate);
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
                        $eventStartDate = sprintf("%s", trim($tmpStartDate));
                        $eventEndDate = sprintf("%s", trim($tmpEndDate));
                    } else {
                        $eventStartDate = sprintf("%s|%s", $eventStartDate, trim($tmpStartDate));
                        $eventEndDate = sprintf("%s|%s", $eventEndDate, trim($tmpEndDate));
                    }
                    $k++;
                }
            }
            $resultingData[] = [
                'accessionNumber' => strtoupper($record['Identifier']),
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
                'donorName' => $this->processDonor($record['Donor'], strtoupper($record['Identifier'])),
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

    protected function processDonor($donorName, $accessionNumber)
    {

        $accessionYear = $this->parseAccessionNumberForYear($accessionNumber);
        switch($donorName)
        {
            case "Cooperative Extension Service":
                if($accessionYear >= 1984 && $accessionYear <= 1997) {
                    return "Division of Cooperative Extension";
                } else if ($accessionYear >= 1998) {
                    return "Kansas State Research and Extension";
                } else {
                    return "***LOOKAT***";
                }
                break;
            case "Media Relations":
                if($accessionYear >= 2000 && $accessionYear <= 2007)
                {
                    return "Media Relations and Marketing";
                } else if ($accessionYear >= 2008 && $accessionYear <= 2009) {
                    return "Media Relations";
                } else if ($accessionYear == 2010) {
                    return "News Services";
                }else if($accessionYear == 2011) {
                    return "News Media Services";
                } else if ($accessionYear >= 2012) {
                    return "Division of Communications and Marketing";
                } else {
                    return "***LOOKAT***";
                }
                break;
            case "University News":
                if($accessionYear == 1988) {
                    return "News and Information";
                } else if ($accessionYear >= 1991 && $accessionYear <= 1994) {
                    return "News Services";
                } else {
                    return "***LOOKAT***";
                }
                break;
            case "University Relations":
                if($accessionYear >= 1984 && $accessionYear <= 1985) {
                    return "Office of University Relations";
                } else if($accessionYear == 1986) {
                    return "Office of Public Relations and Communication";
                } else if ($accessionYear >= 1987 && $accessionYear <= 1988) {
                    return "News and Information";
                }else {
                    return "***LOOKAT***";
                }
                break;
            case "":
                return "";
                break;
            case "(George F. Thompson)":
                return "Unknown";
                break;
            case "[James A. McCain?]":
                return "Unknown";
                break;
            case "[YMCA]":
                return "YMCA of K-State";
                break;
            case "4-H Youth Programs":
                return "Kansas 4-H Youth Programs";
                break;
            case "A. Bower Sageser":
                return "A. Bower Sageser";
                break;
            case "A. Q. Miller School of Journalism and Mass Communication":
                return "A. Q. Miller School of Journalism and Mass Communication";
                break;
            case "A.Q. Miller School of Journalism & Mass Communication: Gloria Freeland":
                return "A. Q. Miller School of Journalism and Mass Communication";
                break;
            case "A.Q. Miller School of Journalism & Mass Communication: Gloria Freeland & Marlene Franke":
                return "A. Q. Miller School of Journalism and Mass Communication";
                break;
            case "A.Q. Miller School of Journalism and Mass Communication":
                return "A. Q. Miller School of Journalism and Mass Communication";
                break;
            case "A.Q. Miller School of Journalism and Mass Communications":
                return "A. Q. Miller School of Journalism and Mass Communication";
                break;
            case "Abby Lindsey Marlatt Estate":
                return "Estate of Abby Lindsey Marlatt";
                break;
            case "Academic Personnel Office":
                return "Office of Academic Personnel";
                break;
            case "Admin. & Finance, VP":
                return "Office of the Vice President for Administration and Finance";
                break;
            case "Administration & Finance, VP":
                return "Office of the Vice President for Administration and Finance";
                break;
            case "Administrative Services Office, Hale Library":
                return "University Libraries, Administrative Services";
                break;
            case "Ag Exp Station":
                return "Kansas Agricultural Experiment Station";
                break;
            case "Ag. Exp. Sta. (publications)":
                return "Kansas Agricultural Experiment Station";
                break;
            case "Ag. Exp. Station":
                return "Kansas Agricultural Experiment Station";
                break;
            case "Ag. Exp. Station, Publications":
                return "Kansas Agricultural Experiment Station";
                break;
            case "Ag. Res. Instr.":
                return "College of Agriculture, Resident Instruction";
                break;
            case "Ag. Resident Instruction":
                return "College of Agriculture, Resident Instruction";
                break;
            case "Ag. Resident Instruction (Linda Buchheister)":
                return "College of Agriculture, Resident Instruction";
                break;
            case "Agricultural Economics":
                return "Department of Agricultural Economics";
                break;
            case "Agricultural Engineering":
                return "Department of Agricultural Engineering";
                break;
            case "Agricultural Exp. Station (Stanley Leland)":
                return "Kansas Agricultural Experiment Station";
                break;
            case "Agriculture":
                return "College of Agriculture";
                break;
            case "Agriculture, Assoc. Dean/Dir of Resident Instruction":
                return "Associate Dean of Agriculture and Director of Resident Instruction";
                break;
            case "Agriculture, Dean":
                return "Dean of Agriculture";
                break;
            case "Agriculture, Dean of":
                return "Dean of Agriculture";
                break;
            case "Agronomy":
                return "Department of Agronomy";
                break;
            case "Agronomy  (Floyd Smith)":
                return "Department of Agronomy";
                break;
            case "Agronomy  (O. Bidwell)":
                return "Department of Agronomy";
                break;
            case "Agronomy (O. Bidwell)":
                return "Department of Agronomy";
                break;
            case "Alan Greer":
                return "Alan Greer";
                break;
            case "Alberta Anthony":
                return "Alberta Anthony";
                break;
            case "Alden Green":
                return "Alden Green";
                break;
            case "Alejandro Suñe; purchased at antique store":
                return "Alejandro Suñe";
                break;
            case "Alice Roper":
                return "Alice Roper";
                break;
            case "Alison Wheatley":
                return "Alison Wheatley";
                break;
            case "Allen N. Webb":
                return "Allen N. Webb";
                break;
            case "Alma Williams":
                return "Alma Williams";
                break;
            case "Alpha of Clovia":
                return "National Association of Clovia Sorority, Alpha chapter";
                break;
            case "Alumni Association":
                return "Kansas State University Alumni Association";
                break;
            case "American Council on Consumer Interests":
                return "American Council on Consumer Interests";
                break;
            case "American Ethnic Studies Department":
                return "Department of American Ethnic Studies";
                break;
            case "Americans for Fairness in Lending":
                return "Americans for Fairness in Lending";
                break;
            case "Amy Hartman; Extension publications":
                return "Kansas State Research and Extension";
                break;
            case "Ana Elnora Owens, daughter of George Washington Owens.":
                return "Ana Elnora Owens";
                break;
            case "Anderson - Lincoln":
                return "Rex and Lucille Anderson";
                break;
            case "Animal Husbandry":
                return "Department of Animal Husbandry";
                break;
            case "Animal Science (Miles McKee)":
                return "Department of Animal Sciences and Industry";
                break;
            case "Ann Roberts Gish":
                return "Ann Roberts Gish";
                break;
            case "Ann Smit":
                return "Ann Smit";
                break;
            case "Anthony R. Crawford":
                return "Anthony R. Crawford";
                break;
            case "Anthony R. Crawford, donor, purchase off eBay":
                return "Anthony R. Crawford";
                break;
            case "Anthony R. Crawford, donor; purchase off eBay":
                return "Anthony R. Crawford";
                break;
            case "Anthony R. Crawford, eBay purchase":
                return "Anthony R. Crawford";
                break;
            case "Antonia Pigno":
                return "Antonia Pigno";
                break;
            case "Antonia Pigno (via John Vander Velde)":
                return "Antonia Pigno";
                break;
            case "Architecture & Design, Dean (Diane Potts)":
                return "Dean of Architecture and Design";
                break;
            case "Architecture, Planning & Design":
                return "College of Architecture, Planning, and Design";
                break;
            case "Arizona Consumers Council":
                return "Arizona Consumers Council";
                break;
            case "Arlene E. (Abel) Luchsinger":
                return "Arlene E. (Abel) Luchsinger";
                break;
            case "Arthur F. Peine":
                return "Arthur F. Peine";
                break;
            case "Arts and Science (Phoebe Samelson)":
                return "College of Arts and Sciences";
                break;
            case "Arts and Science, Dean of":
                return "Dean of Arts and Sciences";
                break;
            case "Arts and Sciences, Dean of":
                return "Dean of Arts and Sciences";
                break;
            case "Asgard Press":
                return "Äsgard Press";
                break;
            case "Assistant to the Dean of Agriculture":
                return "Assistant to the Dean of Agriculture and Director of KSRE";
                break;
            case "Assistant to the Dean of Agriculture and Director of KSRE":
                return "Assistant to the Dean of Agriculture and Director of KSRE";
                break;
            case "Associate Provost":
                return "Associate Provost";
                break;
            case "Associate Provost (Robert Kruh)":
                return "Associate Provost";
                break;
            case "Association of Residence Halls (Chuck Werring, Housing and Dining)":
                return "Association of Residence Halls";
                break;
            case "Athletic (Looney)":
                return "Department of Intercollegiate Athletics";
                break;
            case "Athletics, RJ Bokleman":
                return "K-State Athletics";
                break;
            case "B Easterling":
                return "B Easterling";
                break;
            case "Barbara Drayer Booth":
                return "Barbara Drayer Booth";
                break;
            case "Barbara Ketterman Pendleton":
                return "Barbara Ketterman Pendleton";
                break;
            case "Barbara Springer":
                return "Barbara Springer";
                break;
            case "Barbara Stevens":
                return "Barbara W. Stevens";
                break;
            case "Barbara W. Stevens (granddaughter of T. Will)":
                return "Barbara W. Stevens";
                break;
            case "Barry L. Flinchbaugh":
                return "Barry L. Flinchbaugh";
                break;
            case "Barry Michie":
                return "Barry Michie";
                break;
            case "Beach Art Museum (J. Reichman)":
                return "Marianna Kistler Beach Museum of Art";
                break;
            case "Beach Museum (Edward Larson)":
                return "Marianna Kistler Beach Museum of Art";
                break;
            case "Beach Museum of Art":
                return "Marianna Kistler Beach Museum of Art";
                break;
            case "Ben A. Smith, Elementary Education":
                return "Ben A. Smith";
                break;
            case "Bert McArthur":
                return "Bert McArthur";
                break;
            case "Beth Givens, Executive Director Emeritus":
                return "Beth Givens";
                break;
            case "Bill Arck":
                return "Bill Arck";
                break;
            case "Bill Richter":
                return "Bill Richter";
                break;
            case "Biology (Jerry Weis)":
                return "Division of Biology";
                break;
            case "Black Faculty and Staff Alliance":
                return "Black Faculty and Staff Alliance";
                break;
            case "Black Student Union":
                return "Kansas State University Black Student Union";
                break;
            case "Bob Newsome":
                return "Bob Newsome";
                break;
            case "Bonnie Lynn-Sherow":
                return "Bonnie Lynn-Sherow";
                break;
            case "Book dealer":
                return "Lloyd Zimmer, Books and Maps";
                break;
            case "Brand Used Works Auction House":
                return "Brand Used Works Auction House";
                break;
            case "Brenda Hanger":
                return "Brenda Hanger";
                break;
            case "Brian Niehoff":
                return "Brian Niehoff";
                break;
            case "Brice Hobrock":
                return "Brice Hobrock";
                break;
            case "Brock Dale":
                return "Brock Dale";
                break;
            case "Bruce McMillan":
                return "Bruce McMillan";
                break;
            case "Budget Office":
                return "Budget Office";
                break;
            case "Budget Office via Brice Hobrock":
                return "Budget Office";
                break;
            case "Business Affairs, VP":
                return "Vice President for Business Affairs";
                break;
            case "C. Clyde Jones":
                return "C. Clyde Jones";
                break;
            case "Campaign for Nonviolence Committee":
                return "Campaign for Nonviolence Committee";
                break;
            case "Campus Planning and Project Management":
                return "Office of Campus Planning and Project Management";
                break;
            case "Career and Employment Services":
                return "Office of Career and Employment Services";
                break;
            case "Carl Rehfeld":
                return "Carl Rehfeld";
                break;
            case "Caroline F. Peine":
                return "Caroline F. Peine";
                break;
            case "Caroline Peine":
                return "Caroline F. Peine";
                break;
            case "Carolyn Page & Roy Zarucchi":
                return "Carolyn Page and Roy Zarucchi";
                break;
            case "Cathy Doane":
                return "Cathy Doane";
                break;
            case "Center for Advocacy, Response, and Education":
                return "Center for Advocacy, Response, and Education";
                break;
            case "Center for Aging":
                return "Center for Aging";
                break;
            case "Center for Faculty Evaluation (William Cashin)":
                return "Center for Faculty Evaluation";
                break;
            case "Center for Student and Professional Services (Rosemary Woods)":
                return "Center for Student and Professional Services";
                break;
            case "Center for Student Development":
                return "Center for Student Development";
                break;
            case "Center for Student Development (Caroline Peine)":
                return "Center for Student Development";
                break;
            case "Center for the Advancement of Teaching and Learning":
                return "Center for the Advancement of Teaching and Learning";
                break;
            case "CERN":
                return "Consumer Education Resource Network";
                break;
            case "CES":
                return "Kansas State Research and Extension";
                break;
            case "CES, M. Hightower":
                return "Kansas State Research and Extension";
                break;
            case "Chapel Hill Books":
                return "Chapel Hill Books";
                break;
            case "Char Simser":
                return "Char Simser";
                break;
            case "Charlene Willard Wilkinson":
                return "Charlene Willard Wilkinson";
                break;
            case "Charles and Lois Deyoe":
                return "Charles and Lois Deyoe";
                break;
            case "Charles Deyoe":
                return "Charles Deyoe";
                break;
            case "Charles L. Marshall, Jr.":
                return "Charles L. Marshall, Jr.";
                break;
            case "Charles Longwell":
                return "Charles Longwell";
                break;
            case "Charles Marshall, Jr.":
                return "Charles L. Marshall, Jr.";
                break;
            case "Charles R. Bennett":
                return "Charles R. Bennett";
                break;
            case "Chester A. Peters":
                return "Chester A. Peters";
                break;
            case "Chimes Junior Honorary Society":
                return "Chimes Junior Honorary Society, Kansas State University chapter";
                break;
            case "Chimes Junior Honorary Society (KSU chapter)":
                return "Chimes Junior Honorary Society, Kansas State University chapter";
                break;
            case "Christine Demarcus":
                return "Christine Demarcus";
                break;
            case "Christopher J.J. Thiry":
                return "Christopher J.J. Thiry";
                break;
            case "Chuck Bartlett":
                return "Chuck Bartlett";
                break;
            case "Cindy Harris":
                return "Cindy Harris";
                break;
            case "Cindy Von Elling":
                return "Cindy Von Elling";
                break;
            case "Classified Affairs Committee":
                return "Classified Affairs Committee";
                break;
            case "Claudia Cerny":
                return "Claudia Cerny";
                break;
            case "Clay County Historical Society":
                return "Clay County Historical Society";
                break;
            case "Clementine Paddleford Estate":
                return "Estate of Clementine Paddleford";
                break;
            case "Clothing, & Textiles":
                return "Department of Clothing, Textiles, and Interior Design";
                break;
            case "Clothing, Textile & Interior Design":
                return "Department of Clothing, Textiles, and Interior Design";
                break;
            case "Clothing, Textiles &  Interior Design":
                return "Department of Clothing, Textiles, and Interior Design";
                break;
            case "Clovia (Mary Radnor)":
                return "National Association of Clovia Sorority, Alpha chapter";
                break;
            case "Clyde Howard, Office of Affirmative Action":
                return "Office of Affirmative Action";
                break;
            case "Coleen N. Klatt":
                return "Coleen N. Klatt";
                break;
            case "College of Agriculture":
                return "College of Agriculture";
                break;
            case "College of Agriculture Academic Programs":
                return "College of Agriculture, Academic Programs";
                break;
            case "College of Agriculture Academic  Programs":
                return "College of Agriculture, Academic Programs";
                break;
            case "College of Agriculture, Academic Programs; Kevin Donnelly":
                return "College of Agriculture, Academic Programs";
                break;
            case "College of Agriculture, Dean’s Office":
                return "Office of the Dean of Agriculture";
                break;
            case "College of Agriculture, Steve Graham":
                return "Assistant to the Dean of Agriculture and Director of KSRE";
                break;
            case "College of Architecture, Planning and Design (Ray Weisenburger)":
                return "College of Architecture, Planning, and Design";
                break;
            case "College of Arts and Sciences":
                return "College of Arts and Sciences";
                break;
            case "College of Education":
                return "College of Education";
                break;
            case "College of Education, Center for Student & Professional Services":
                return "College of Education, Center for Student and Professional Services";
                break;
            case "College of Engineering":
                return "College of Engineering";
                break;
            case "College of Engineering, Associate Dean's Office":
                return "Office of the Associate Dean of Engineering ";
                break;
            case "College of Engineering, Dean’s Office":
                return "Office of the Dean of Engineering";
                break;
            case "College of Engineering: Brooke Hain":
                return "College of Engineering";
                break;
            case "College of Home Economics":
                return "College of Home Economics";
                break;
            case "College of Human Ecology ":
                return "College of Human Ecology";
                break;
            case "College of Human Ecology ":
                return "College of Human Ecology";
                break;
            case "College of Human Ecology":
                return "College of Human Ecology";
                break;
            case "College of Human Ecology, Dean’s Office (Debby Hiett)":
                return "Office of the Dean of Human Ecology";
                break;
            case "College of Human Ecology: Jean Sego":
                return "College of Human Ecology";
                break;
            case "College of Human EcologyÂ ":
                return "College of Human Ecology";
                break;
            case "College of Veterinary Medicine":
                return "College of Veterinary Medicine";
                break;
            case "College of Veterinary Medicine Library":
                return "College of Veterinary Medicine Library";
                break;
            case "Computing and Network Services (Ken Conrow)":
                return "Computing and Network Services";
                break;
            case "Continuing Education":
                return "Division of Continuing Education";
                break;
            case "Continuing Education (Beth Unger)":
                return "Division of Continuing Education";
                break;
            case "Continuing Education (Linda Morse)":
                return "Division of Continuing Education";
                break;
            case "Continuing Education (Peg Wherry)":
                return "Division of Continuing Education";
                break;
            case "Controller’s Office":
                return "Office of the Controller";
                break;
            case "Controller’s Office (William Sesler)":
                return "Office of the Controller";
                break;
            case "Controller's Office":
                return "Office of the Controller";
                break;
            case "Coop. Ext. Ser.":
                return "Division of Cooperative Extension";
                break;
            case "Coop. Ext. Ser. (Marlene Hightower)":
                return "Division of Cooperative Extension";
                break;
            case "Coop. Ext. Ser.-Radio, TV":
                return "Department of Extension Radio, Television, and Film";
                break;
            case "Cooperative Extension Service (Agronomy)":
                return "Kansas State Research and Extension";
                break;
            case "Cooperative Extension Service Agronomy":
                return "Kansas State Research and Extension";
                break;
            case "Cooperative Extension Service, Marlene Hightower":
                return "Kansas State Research and Extension";
                break;
            case "Cooperative Extension Service, Northeast office":
                return "Division of Cooperative Extension, Northeast office";
                break;
            case "Cooperative Extension Service: Radio, TV, and Film":
                return "Department of Extension Radio, Television, and Film";
                break;
            case "Cooperative Extension Services (Robert Johnson)":
                return "Division of Cooperative Extension";
                break;
            case "Cornelia Flora":
                return "Cornelia Flora";
                break;
            case "Cornelia Flora (Eugene Kremer)":
                return "Cornelia Flora";
                break;
            case "County of Los Angeles (CA), Department of Agricultural Commissioner":
                return "County of Los Angeles (CA), Department of Agricultural Commissioner";
                break;
            case "Craig Miner":
                return "Craig Miner";
                break;
            case "Craig Parker":
                return "Craig Parker";
                break;
            case "Curtis Kastner":
                return "Curtis Kastner";
                break;
            case "Cynthia J. Sorrick":
                return "Cynthia J. Sorrick";
                break;
            case "D.D. Eisenhower Library":
                return "Dwight D. Eisenhower Presidential Library";
                break;
            case "Daisy E. Atkinson":
                return "Daisy E. Atkinson";
                break;
            case "Dan D. Casement & heirs":
                return "Dan D. Casement and heirs";
                break;
            case "Dan Lykins via Myra Gordon":
                return "Dan Lykins";
                break;
            case "Dance, Director of (Luke Kahlich)":
                return "Department of Speech, Director of Dance";
                break;
            case "David Allen":
                return "David B. Allen";
                break;
            case "David B. Allen for department":
                return "Department of Library Automation Development";
                break;
            case "David Dary":
                return "David Dary";
                break;
            case "David non Riesen":
                return "David von Riesen";
                break;
            case "David Schafer":
                return "David Schafer";
                break;
            case "David Smies":
                return "David Smies";
                break;
            case "Dean of Architecture, Planning and Design":
                return "Dean of Architecture, Planning and Design";
                break;
            case "Dean of Home Economics (Ruth Hoeflin)":
                return "Dean of Home Economics";
                break;
            case "Dean of Human Ecology":
                return "Dean of Human Ecology";
                break;
            case "Dean of Students ( Earl Nolting)":
                return "Dean of Students";
                break;
            case "Dean/Vice Pres Student Life":
                return "Vice President for Student Life and Dean of Students";
                break;
            case "Dean/Vice Pres. Student Life":
                return "Vice President for Student Life and Dean of Students";
                break;
            case "Deb Dunsford":
                return "Deb Dunsford";
                break;
            case "Debbie Madsen":
                return "Debbie Madsen";
                break;
            case "Deborah Hause":
                return "Deborah Hause";
                break;
            case "Delta Chapter of Phi Beta Sigma":
                return "Phi Beta Sigma, Delta chapter";
                break;
            case "Delta Chapter, Phi Beta Sigma Fraternity":
                return "Phi Beta Sigma, Delta chapter";
                break;
            case "Delta Sigma Phi":
                return "Delta Sigma Phi, Alpha Upsilon chapter";
                break;
            case "Dennis McKinney":
                return "Dennis McKinney";
                break;
            case "Department of Agronomy":
                return "Department of Agronomy";
                break;
            case "Department of Agronomy (Gerry Posler)":
                return "Department of Agronomy";
                break;
            case "Department of Animal Sciences and Industry":
                return "Department of Animal Sciences and Industry";
                break;
            case "Department of Apparel, Textiles, and Interior Design":
                return "Department of Apparel, Textiles, and Interior Design";
                break;
            case "Department of Art":
                return "Department of Art";
                break;
            case "Department of Biological and Agricultural Engineering":
                return "Department of Biological and Agricultural Engineering";
                break;
            case "Department of Chemistry":
                return "Department of Chemistry";
                break;
            case "Department of Clothing, Textiles, and Interior Design":
                return "Department of Clothing, Textiles, and Interior Design";
                break;
            case "Department of Communications and Agricultural Education":
                return "Department of Communications and Agricultural Education";
                break;
            case "Department of Electrical and Computer Engineering":
                return "Department of Electrical and Computer Engineering";
                break;
            case "Department of Entomology":
                return "Department of Entomology";
                break;
            case "Department of Grain Science and Industry":
                return "Department of Grain Science and Industry";
                break;
            case "Department of History":
                return "Department of History";
                break;
            case "Department of Mathematics":
                return "Department of Mathematics";
                break;
            case "Department of Mechanical Engineering":
                return "Department of Mechanical Engineering";
                break;
            case "Department of Music":
                return "Department of Music";
                break;
            case "Dept. of Agronomy":
                return "Department of Agronomy";
                break;
            case "Dept. of Grain Science & Industry":
                return "Department of Grain Science and Industry";
                break;
            case "Dept. of Music":
                return "Department of Music";
                break;
            case "Dept. of Plant Pathology":
                return "Department of Plant Pathology";
                break;
            case "Dept. of Sociology, Anthropology & Social Work":
                return "Department of Sociology, Anthropology, and Social Work";
                break;
            case "Diana Farmer":
                return "Diana Farmer";
                break;
            case "Diane E. Bratton":
                return "Diane E. Bratton";
                break;
            case "Disability Support Services; Gretchen Holden":
                return "Office of Disability Support Services";
                break;
            case "Disabled Student Services (Gretchen Holden)":
                return "Office of Disability Support Services";
                break;
            case "Division of Biology":
                return "Division of Biology";
                break;
            case "Division of Communications and Marketing":
                return "Division of Communications and Marketing";
                break;
            case "Division of Continuing Education":
                return "Division of Continuing Education";
                break;
            case "Division of Continuing Education/Vicky Horan":
                return "Division of Continuing Education";
                break;
            case "Division of Facilities":
                return "Division of Facilities";
                break;
            case "Division of Financial Services":
                return "Division of Financial Services";
                break;
            case "Division. of Continuing  Education":
                return "Division of Continuing Education";
                break;
            case "Don Good":
                return "Don L. Good";
                break;
            case "Don L. Good":
                return "Don L. Good";
                break;
            case "Don Rude":
                return "Don Rude";
                break;
            case "Donald Setser (Dept. of Chemistry)":
                return "Donald Setser";
                break;
            case "Donald Stuart Pady":
                return "Donald Stuart Pady";
                break;
            case "Donna C. Roper":
                return "Donna C. Roper";
                break;
            case "Donna Dehn":
                return "Donna Dehn";
                break;
            case "Donna Wilson and Donnie W. Otis":
                return "Donna Wilson and Donnie W. Otis";
                break;
            case "Doris E. Scott":
                return "Doris E. Scott";
                break;
            case "Dorothy Faulk":
                return "Dorothy Faulk";
                break;
            case "Dorothy L. Thompson Lecture Series Committee":
                return "Dorothy L. Thompson Lecture Series Committee";
                break;
            case "Dorothy L. Thomson Lecture Series Committee":
                return "Dorothy L. Thompson Lecture Series Committee";
                break;
            case "Dorothy L. Thomson Lecture Series Committee: Melissa Linenberger":
                return "Dorothy L. Thompson Lecture Series Committee";
                break;
            case "Dorothy L. Thomson Lecture Series Committee: Susana Valdovinos":
                return "Dorothy L. Thompson Lecture Series Committee";
                break;
            case "Dorothy Nelson":
                return "Dorothy Nelson";
                break;
            case "Doug Jardine":
                return "Doug Jardine";
                break;
            case "Dow Chemical Multicultural Resource Center":
                return "Dow Chemical Multicultural Resource Center";
                break;
            case "Dow Chemical Multicultural Resource Center/University Libraries: Melia Fritch":
                return "Dow Chemical Multicultural Resource Center";
                break;
            case "DOW Multicultural Resource Center":
                return "Dow Chemical Multicultural Resource Center";
                break;
            case "Duane Acker":
                return "Duane Acker";
                break;
            case "Earl Holle":
                return "Earl Holle";
                break;
            case "Earl Hupp":
                return "Earl Hupp";
                break;
            case "eBay":
                return "Purchase from eBay seller";
                break;
            case "eBay from dwlfwdbk@gwi.net":
                return "Purchase from eBay seller";
                break;
            case "eBay purchase":
                return "Purchase from eBay seller";
                break;
            case "eBay purchase; gift of Anthony R. Crawford":
                return "Anthony R. Crawford";
                break;
            case "Ecumenical Campus Ministries":
                return "Ecumenical Campus Ministries";
                break;
            case "Ed Call":
                return "Edward Call";
                break;
            case "Edgar Tidwell":
                return "Edgar Tidwell";
                break;
            case "Education (John D. Steffen)":
                return "Office of Educational and Student Services";
                break;
            case "Educational & Student Ser.":
                return "Office of Educational and Student Services";
                break;
            case "Edward and Anita Metzen":
                return "Edward and Anita Metzen";
                break;
            case "Edward Call":
                return "Edward Call";
                break;
            case "Edward Evans (through Theodore Barkeley)":
                return "Edward Evans";
                break;
            case "Eilleen Roberts":
                return "Eilleen Roberts";
                break;
            case "Eleanor Mackey Ferguson":
                return "Eleanor Mackey Ferguson";
                break;
            case "Elizabeth Orr":
                return "Elizabeth Orr";
                break;
            case "Ellen L. Feldhausen":
                return "Ellen L. Feldhausen";
                break;
            case "Emily Mark":
                return "Emily Mark";
                break;
            case "Emily Thackrey":
                return "Emily Thackrey";
                break;
            case "Emmett Skinner, Jr.":
                return "Emmett Skinner, Jr.";
                break;
            case "Employee Relations":
                return "Office of Employee Relations";
                break;
            case "Engineering, College of":
                return "College of Engineering";
                break;
            case "Engineering, Dean  (Betty Slemen)":
                return "Dean of Engineering";
                break;
            case "Entomology (H. Knutson)":
                return "Department of Entomology";
                break;
            case "Entomology (V. Wright)":
                return "Department of Entomology";
                break;
            case "Epsilon Sigma Phi":
                return "Epsilon Sigma Phi, Alpha Rho chapter";
                break;
            case "Epsilon Sigma Phi, Alpha Rho chapter":
                return "Epsilon Sigma Phi, Alpha Rho chapter";
                break;
            case "Eric Dover":
                return "Eric Dover";
                break;
            case "Ermal Dearborn Colby":
                return "Ermal Dearborn Colby";
                break;
            case "Ernie and Bonnie Barrett":
                return "Ernie and Bonnie Barrett";
                break;
            case "Ernie Roll":
                return "Ernie Roll";
                break;
            case "Estate of Alfred Nelson":
                return "Estate of Alfred Nelson";
                break;
            case "Estate of Alice C. Nichols":
                return "Estate of Alice C. Nichols";
                break;
            case "Estate of Carlyle Woelfer":
                return "Estate of Carlyle Woelfer";
                break;
            case "Estate of Frank Caldwell Hershberger":
                return "Estate of Frank Caldwell Hershberger";
                break;
            case "Estate of Paul Barton Grover, Sr.":
                return "Estate of Paul Barton Grover, Sr.";
                break;
            case "Estella Barnum Shelley":
                return "Estella Barnum Shelley";
                break;
            case "Evelyn Brown":
                return "Evelyn Brown";
                break;
            case "Extension Agronomy; David Whitney":
                return "Kansas State Research and Extension";
                break;
            case "Extension Family Studies and Human Services":
                return "Kansas State Research and Extension";
                break;
            case "F. Robert Henderson":
                return "F. Robert Henderson";
                break;
            case "Facilities Planning":
                return "Office of Facilities Planning";
                break;
            case "Faculty Senate":
                return "Faculty Senate";
                break;
            case "Faculty Senate Office":
                return "Faculty Senate";
                break;
            case "Faculty Senate office":
                return "Faculty Senate";
                break;
            case "Faculty Senate Office: Candace LaBerge":
                return "Faculty Senate";
                break;
            case "FAR-MAR-CO, Inc":
                return "FAR-MAR-CO, Inc";
                break;
            case "Fay E. Gish (from John Vander Velde)":
                return "Fay E. Gish";
                break;
            case "Felisa & Annabel Osburn":
                return "Felisa and Annabel Osburn";
                break;
            case "Florence Helmick West":
                return "Florence Helmick West";
                break;
            case "Florence Lippenberger":
                return "Florence Lippenberger";
                break;
            case "Florence Mason":
                return "Florence Mason";
                break;
            case "Florence Walker":
                return "Florence Walker";
                break;
            case "Food and Feed Grain Institute":
                return "Food and Feed Grain Institute";
                break;
            case "Former K-Laires faculty advisor":
                return "K-Laires";
                break;
            case "Found in surplus desk":
                return "University Libraries, Administrative Services";
                break;
            case "Frances Johnson (daughter of Ruth Stover)":
                return "Frances Johnson";
                break;
            case "Frances Lewis":
                return "Frances Lewis";
                break;
            case "Frank S. Shelton":
                return "Frank S. Shelton";
                break;
            case "Fred Higginson":
                return "Fred Higginson";
                break;
            case "Friends of Konza":
                return "Friends of Konza Prairie";
                break;
            case "Friends of the Carson City Library":
                return "Friends of the Carson City Library";
                break;
            case "From gifts unit in Hale Library; found in gift of Earl D. Hansing":
                return "Earl D. Hansing";
                break;
            case "Ft. Hays Experiment Station":
                return "Agricultural Experiment Station—Hays";
                break;
            case "Gary G. West":
                return "Gary G. West";
                break;
            case "Geology":
                return "Department of Geology";
                break;
            case "Geology (collection development)":
                return "Department of Geology";
                break;
            case "Geology Dept. (collection development)":
                return "Department of Geology";
                break;
            case "George Brunn":
                return "George Brunn";
                break;
            case "George Clark; found at College of Veterinary Medicine auction":
                return "George Clark";
                break;
            case "George M. Munger Riggle":
                return "George M. Munger Riggle";
                break;
            case "George Merrick Munger":
                return "George Merrick Munger";
                break;
            case "George Minkoff (purchase w/NEH funds)":
                return "George Minkoff";
                break;
            case "George R. Hanson":
                return "George R. Hanson";
                break;
            case "George V. Meuller":
                return "George V. Mueller";
                break;
            case "George V. Mueller":
                return "George V. Mueller";
                break;
            case "Gerald L. Mowry":
                return "Gerald L. Mowry";
                break;
            case "Gerry L. Posler":
                return "Gerry L. Posler";
                break;
            case "Gloria Freeland":
                return "Gloria Freeland";
                break;
            case "Gordon G. Lill":
                return "Gordon G. Lill";
                break;
            case "Gordon Parks":
                return "Gordon Parks";
                break;
            case "Grace Flenthrope":
                return "Grace Flenthrope";
                break;
            case "Graduate School":
                return "Graduate School";
                break;
            case "Graduate School (Robert Kruh)":
                return "Graduate School";
                break;
            case "Grain Science & Industry":
                return "Department of Grain Science and Industry";
                break;
            case "Grain Science and Industry (Mark Fowler)":
                return "Department of Grain Science and Industry";
                break;
            case "Greek Affairs":
                return "Office of Greek Affairs";
                break;
            case "Gustavo L. Rosania, Class of 1952 (Mechanical Engineering) via Richard Hayter, College of Engineering":
                return "Gustavo L. Rosania";
                break;
            case "Gwin":
                return "Paul B. Gwin";
                break;
            case "Hall Ottaway, Bookseller":
                return "Hal Ottaway";
                break;
            case "Harold R. Fatzer":
                return "Harold R. Fatzer";
                break;
            case "Harriet A. Parkerson, et. al.":
                return "Harriet A. Parkerson, et. al.";
                break;
            case "Harry H. Halbower, Jr.":
                return "Harry H. Halbower, Jr.";
                break;
            case "Hazardous Substance Research Center":
                return "Hazardous Substance Research Center";
                break;
            case "Hazel Marie Scott Sherwood":
                return "Hazel Marie Scott Sherwood";
                break;
            case "Helen Gilles":
                return "Helen Gilles";
                break;
            case "Henry F. Kupfer":
                return "Henry F. Kupfer";
                break;
            case "Henry Kubik":
                return "Henry Kubik";
                break;
            case "Historic Costume and Textile Museum: Marla Day":
                return "Historic Costume and Textile Museum";
                break;
            case "History":
                return "Department of History";
                break;
            case "History  (Robin Higham)":
                return "Department of History";
                break;
            case "History (Robin Higham)":
                return "Department of History";
                break;
            case "History Dept":
                return "Department of History";
                break;
            case "History Dept (Homer Socolofsky)":
                return "Department of History";
                break;
            case "History, Dept. of":
                return "Department of History";
                break;
            case "Holden, Gretchen":
                return "Office of Special Services";
                break;
            case "Homer Socolofsky":
                return "Homer Socolofsky";
                break;
            case "Homer Socolofsky / Robin Higham":
                return "Kansas State University Historical Society";
                break;
            case "Homor Socolofsky":
                return "Kansas State University Historical Society";
                break;
            case "Horticulture (through Facilities Planning)":
                return "Office of Facilities Planning";
                break;
            case "Horticulture, Forestry and Recreational Services":
                return "Department of Horticulture, Forestry, and Recreation Resources";
                break;
            case "Hotel, Rest., IM & Diet.":
                return "Department of Hotel, Restaurant, Institution Management and Dietetics";
                break;
            case "Housing & Dining Services: James Hodges":
                return "Office of Housing and Dining Services";
                break;
            case "Housing and Dining Services":
                return "Office of Housing and Dining Services";
                break;
            case "Hugh S. Walker, Jr.":
                return "Hugh S. Walker, Jr.";
                break;
            case "Human Ecology":
                return "College of Human Ecology";
                break;
            case "Human Ecology  (K. Pence)":
                return "College of Human Ecology";
                break;
            case "Human Ecology  (R. Jean Sego)":
                return "College of Human Ecology";
                break;
            case "Human Ecology  (Ruth Hoeflin)":
                return "College of Human Ecology";
                break;
            case "Human Ecology, College of (Jean Sego)":
                return "College of Human Ecology";
                break;
            case "Human Resource Services":
                return "Human Resource Services";
                break;
            case "Human Resources":
                return "Office of Human Resources";
                break;
            case "Human Resources (Jean Dancy)":
                return "Office of Human Resources";
                break;
            case "Human Resources-Payroll":
                return "Office of Human Resources";
                break;
            case "Human Resources-Payroll: Patsy Havenstein":
                return "Office of Human Resources";
                break;
            case "Iiams":
                return "Don A. and Barbara Jean (Baker) Iiams";
                break;
            case "Industrial Engineering":
                return "Department of Industrial Engineering";
                break;
            case "Institute for Academic Alliances":
                return "Institute for Academic Alliances";
                break;
            case "Institute for Environmental Research":
                return "Institute for Environmental Research";
                break;
            case "Institutional purchase 2000":
                return "University Libraries";
                break;
            case "Inter Collegiate Athletics":
                return "Department of Intercollegiate Athletics";
                break;
            case "International Programs (William Richter)":
                return "Office of International Programs";
                break;
            case "Iowa State University Special Collections Department":
                return "Iowa State University Special Collections Department";
                break;
            case "J&J Lubrano Music Antiquarians (purchase)":
                return "J & J Lubrano Music Antiquarians";
                break;
            case "J. E. Kammeyer":
                return "J. E. Kammeyer";
                break;
            case "J. R. James":
                return "J. R. James";
                break;
            case "Jack Holl, Dept of History":
                return "Jack Holl";
                break;
            case "Jack Oviatt":
                return "Jack Oviatt";
                break;
            case "Jaclyn Reed":
                return "Jaclyn Reed";
                break;
            case "James C. Carey and the Department of History":
                return "James C. Carey and the Department of History";
                break;
            case "James Carey":
                return "James C. Carey";
                break;
            case "James Cummins Bookseller":
                return "James Cummins Bookseller";
                break;
            case "James Guikema":
                return "James Guikema";
                break;
            case "James Legg":
                return "James Legg";
                break;
            case "James N. Hume":
                return "James N. Hume";
                break;
            case "James R. Jaax":
                return "James R. Jaax";
                break;
            case "Jan Kruh":
                return "Jan Kruh";
                break;
            case "Jan Wissman":
                return "Jan Wissman";
                break;
            case "Jane Butel":
                return "Jane Butel";
                break;
            case "Jane Schillie":
                return "Jane Schillie";
                break;
            case "Janet R. Woodward":
                return "Janet R. Woodward";
                break;
            case "Janet Turner":
                return "Janet Turner";
                break;
            case "Jason P. Holcomb":
                return "Jason P. Holcomb";
                break;
            case "Jean Darbyshire":
                return "University Libraries";
                break;
            case "Jean Davis":
                return "Jean Davis";
                break;
            case "Jeanette James Saxton":
                return "Jeanette James Saxton";
                break;
            case "Jennie Giles":
                return "Jennie Giles";
                break;
            case "Jessica Reichman":
                return "Jessica Reichman";
                break;
            case "Jim and Carolyn Hodgson":
                return "Jim and Carolyn Hodgson";
                break;
            case "Joan Shull":
                return "Joan Shull";
                break;
            case "Joanne Cox Black":
                return "Joanne Cox Black";
                break;
            case "Joel Climenhaga":
                return "Joel Climenhaga";
                break;
            case "John C. Reese":
                return "John C. Reese";
                break;
            case "John Chalmers":
                return "John Chalmers";
                break;
            case "John H. Rust (via Brice Hobrock, Dean of Libraries)":
                return "John H. Rust";
                break;
            case "John M. Lilley":
                return "John M. Lilley";
                break;
            case "John McCulloh":
                return "John McCulloh";
                break;
            case "John Pence/Human Ecology":
                return "College of Human Ecology";
                break;
            case "John R. Coleman (Class 1930)":
                return "John R. Coleman";
                break;
            case "John Selfridge, Dept. of Architecture":
                return "John Selfridge";
                break;
            case "John T. Spike":
                return "John T. Spike";
                break;
            case "John W. Minor":
                return "John W. Minor";
                break;
            case "Johnson Cancer Center":
                return "Johnson Center for Basic Cancer Research";
                break;
            case "Joleen Hill":
                return "Joleen J. Hill";
                break;
            case "Joleen J. Hill":
                return "Joleen J. Hill";
                break;
            case "Jon Wefald":
                return "Jon Wefald";
                break;
            case "Journalism & Mass Comm.":
                return "Department of Journalism and Mass Communications";
                break;
            case "Journalism & Mass Comm. (Roberta Applegate)":
                return "Department of Journalism and Mass Communications";
                break;
            case "Journalism & Mass Comm. (through lib. gifts dept)":
                return "Department of Journalism and Mass Communications";
                break;
            case "Journalism & Mass Communication":
                return "Department of Journalism and Mass Communications";
                break;
            case "Joyce Hoerman Brite":
                return "Joyce Hoerman Brite";
                break;
            case "Joyce Jones, Family and Consumer Science":
                return "School of Family Studies and Human Services, Family and Consumer Science Program";
                break;
            case "Julius T. Willard":
                return "Julius T. Willard";
                break;
            case "K. E. Zeller":
                return "K. E. Zeller";
                break;
            case "KAEHE":
                return "Kansas Association of Extension Home Economists";
                break;
            case "Kansans for ERA (Caroline Peine)":
                return "Kansans for the Equal Rights Amendment";
                break;
            case "Kansas Artificial Breeding Service Unit":
                return "Kansas Artificial Breeding Service Unit";
                break;
            case "Kansas Association for Family and Community Education":
                return "Kansas Association for Family and Community Education";
                break;
            case "Kansas Association for Family and Community Education (Rebecca Wondra)":
                return "Kansas Association for Family and Community Education";
                break;
            case "Kansas City Museum, 3218 Gladstone Blvd, KC, MO":
                return "Kansas City Museum";
                break;
            case "Kansas Collection, Univ. of Kansas":
                return "University of Kansas Libraries, Kenneth Spencer Research Library, Kansas Collection";
                break;
            case "Kansas Extension Homemakers Council":
                return "Kansas Extension Homemakers Council";
                break;
            case "Kansas Music Teachers Association (Virginia Houser)":
                return "Kansas Music Teachers Association";
                break;
            case "Kansas Quarterly":
                return "Kansas Quarterly";
                break;
            case "Kansas Quarterly (Gary Clift)":
                return "Kansas Quarterly";
                break;
            case "Kansas Regents Educational Communications Center":
                return "Kansas Regents Educational Communications Center";
                break;
            case "Kansas State University Foundation":
                return "Kansas State University Foundation";
                break;
            case "Kansas State University Research and Extension":
                return "Epsilon Sigma Phi, Alpha Rho chapter";
                break;
            case "Kansas State University Social Club":
                return "Kansas State University Social Club";
                break;
            case "Karen (Peters) Suther and Steven Peters":
                return "Karen (Peters) Suther and Steven Peters";
                break;
            case "Karla Kubitz":
                return "Karla Kubitz";
                break;
            case "Katherine A. Rogers":
                return "Katherine A. Rogers";
                break;
            case "Kathleen \"Kay\" (Nelson) Wessel":
                return "Kathleen \"Kay\" (Nelson) Wessel";
                break;
            case "Kathleen Lyons Haines":
                return "Kathleen Lyons Haines";
                break;
            case "Kathleen Ward":
                return "Kathleen Ward";
                break;
            case "Kathryn Heyne Worley":
                return "Kathryn Heyne Worley";
                break;
            case "Kathryn R. Halazon":
                return "Kathryn R. Halazon";
                break;
            case "Katie Freshwater":
                return "Katie Freshwater";
                break;
            case "Kevin Christian":
                return "Kevin Christian";
                break;
            case "Kevin Larson":
                return "Kevin Larson";
                break;
            case "Kimberly Smith for Alan Briggs":
                return "Kimberly Smith and Alan Briggs";
                break;
            case "Kingsley W. Greene":
                return "Kingsley W. Greene";
                break;
            case "KKSU":
                return "KKSU radio station";
                break;
            case "Konza Prairie Biological Station":
                return "Konza Prairie Biological Station";
                break;
            case "Konza/Biology":
                return "Konza Prairie Biological Station";
                break;
            case "KS Collection (purchase?)":
                return "University of Kansas Libraries, Kenneth Spencer Research Library, Kansas Collection";
                break;
            case "KS Ext. Assoc. of Family & Consumer Sciences":
                return "Kansas Extension Association of Family and Consumer Sciences";
                break;
            case "KS Home Econ - Student Member Section":
                return "Kansas Home Economists, Student Member Section";
                break;
            case "KSAC":
                return "KSAC radio station";
                break;
            case "KSDB, Journalism and Mass Communication (Candace Walton)":
                return "KSDB radio station";
                break;
            case "K-State Alumin Association":
                return "Kansas State University Alumni Association";
                break;
            case "K-State Alumni Association":
                return "Kansas State University Alumni Association";
                break;
            case "K-State Athletics Communication":
                return "K-State Athletics";
                break;
            case "K-State Athletics via Dean Lori Goetsch":
                return "K-State Athletics";
                break;
            case "K-State Athletics: Mary Gorman":
                return "K-State Athletics";
                break;
            case "K-State Book Network":
                return "K-State Book Network";
                break;
            case "K-State Global Campus":
                return "Kansas State University Global Campus";
                break;
            case "K-State Research and Extension":
                return "Kansas State Research and Extension";
                break;
            case "K-State Research and Extension (Steve Graham)":
                return "Kansas State Research and Extension";
                break;
            case "K-State Research and Extension: Amy Hartman":
                return "Kansas State Research and Extension";
                break;
            case "K-State Technical Journalism Department":
                return "Department of Journalism and Mass Communications";
                break;
            case "K-State Union Program Council (Sylvia Scott)":
                return "K-State Union Program Council";
                break;
            case "K-Stater (Beth Hartenstein)":
                return "K-State Alumni Association";
                break;
            case "KSU Foundation":
                return "Kansas State University Foundation";
                break;
            case "KSU Foundation: Megan Robl":
                return "Kansas State University Foundation";
                break;
            case "KSU Historical Society":
                return "Kansas State University Historical Society";
                break;
            case "KSU Libraries":
                return "University Libraries";
                break;
            case "KSU Libraries: Chair of Technical Services Department":
                return "University Libraries";
                break;
            case "KSU Libraries: Nelda Elder, Chair of Collection Management Department":
                return "University Libraries";
                break;
            case "KSU Libraries: Nelda Elder, Chair of Collections Management":
                return "University Libraries";
                break;
            case "KSU Libraries: Use studies, 1993-1997":
                return "University Libraries";
                break;
            case "KSU Police":
                return "Kansas State University Police Department";
                break;
            case "KSU Research Foundation":
                return "Kansas State University Research Foundation";
                break;
            case "KSU Social Club":
                return "Kansas State University Social Club";
                break;
            case "KSU Social Club (Penny Socolofsky)":
                return "Kansas State University Social Club";
                break;
            case "KSUL (Ann Scott)":
                return "University Libraries";
                break;
            case "KSUL (Brice Hobrock)":
                return "University Libraries";
                break;
            case "KSUL (Diana Farmer)":
                return "University Libraries";
                break;
            case "KSUL (Ellie Marsh)":
                return "University Libraries";
                break;
            case "KSUL (Gayle Willard)":
                return "University Libraries";
                break;
            case "KSUL (ISSA)":
                return "University Libraries";
                break;
            case "KSUL (John Vander Velde)":
                return "University Libraries";
                break;
            case "KSUL (M. Litchfield)":
                return "University Libraries";
                break;
            case "KSUL (Madsen, Debbie)":
                return "University Libraries";
                break;
            case "KSUL (Nancy Wootton)":
                return "University Libraries";
                break;
            case "KSUL (Nelda Elder)":
                return "University Libraries";
                break;
            case "KSUL (Neva White)":
                return "University Libraries";
                break;
            case "KSUL (Virginia Quiring)":
                return "University Libraries";
                break;
            case "KSUL Administrative Office":
                return "University Libraries, Administrative Services";
                break;
            case "KSUL Collection Development":
                return "University Libraries, Collection Development";
                break;
            case "KSUL Microforms/Per.":
                return "University Libraries, Microforms and Periodicals";
                break;
            case "KSUL, Accounting":
                return "University Libraries, Accounting Unit";
                break;
            case "KSUL, Accounting office":
                return "University Libraries, Accounting Unit";
                break;
            case "KSUL, Acquisitions":
                return "University Libraries, Acquisitions";
                break;
            case "KSUL, Administrative Office":
                return "University Libraries, Administrative Services";
                break;
            case "KSUL, ALIS Operations":
                return "University Libraries";
                break;
            case "KSUL, Audio Visual":
                return "University Libraries, Audiovisual Unit";
                break;
            case "KSUL, Audio Visual.":
                return "University Libraries, Audiovisual Unit";
                break;
            case "KSUL, Binding":
                return "University Libraries, Binding";
                break;
            case "KSUL, Chemistry Library":
                return "University Libraries, Chemistry Branch Library";
                break;
            case "KSUL, Collection Development":
                return "University Libraries, Collection Development";
                break;
            case "KSUL, Dean of":
                return "Dean of University Libraries";
                break;
            case "KSUL, Developing Countries":
                return "University Libraries";
                break;
            case "KSUL, Director's Office":
                return "Director of University Libraries";
                break;
            case "KSUL, Gifts":
                return "University Libraries, Gifts";
                break;
            case "KSUL, Gifts & Exchange":
                return "University Libraries, Gifts";
                break;
            case "KSUL, Gifts Dept.":
                return "University Libraries, Gifts";
                break;
            case "KSUL, Govt. Documents":
                return "University Libraries, Government Documents";
                break;
            case "KSUL, Interlibrary Loan":
                return "University Libraries, Interlibrary Loan";
                break;
            case "KSUL, Minorities Center (Antonia Pigno)":
                return "University Libraries, Minorities Resources and Research Center";
                break;
            case "KSUL, Minorities Resource/Research Center (Antonia Pigno)":
                return "University Libraries, Minorities Resources and Research Center";
                break;
            case "KSUL, Science Division":
                return "University Libraries, Science Division";
                break;
            case "KSUL, Serials":
                return "University Libraries, Serials";
                break;
            case "KSUL, Technical Services, Associate Dean of":
                return "University Libraries, Associate Dean of Technical Services";
                break;
            case "KSUL. Acquisitions (Debbie Madsen)":
                return "University Libraries, Acquisitions";
                break;
            case "KSUL. Administrative Office":
                return "University Libraries, Administrative Services";
                break;
            case "KSUL. Bibliographic Control. (Marilyn Turner)":
                return "University Libraries, Bibliographic Control";
                break;
            case "KSUL. Chem/Biochem":
                return "University Libraries, Chemistry Branch Library";
                break;
            case "L.D. Curran via Mitch Ricketts":
                return "L. D. Curran";
                break;
            case "Larry Erickson":
                return "Larry Erickson";
                break;
            case "Larry Erpelding, Academic Programs, College of Agriculture":
                return "College of Agriculture, Academic Programs";
                break;
            case "Larry Kipley":
                return "Larry Kipley";
                break;
            case "Larry Marcellus":
                return "Larry Marcellus";
                break;
            case "Larry Weigel":
                return "Larry Weigel";
                break;
            case "Lawrence E. Will":
                return "Lawrence E. Will";
                break;
            case "Lee Bailey & Paula Quisenberry":
                return "Lee Bailey and Paula Quisenberry";
                break;
            case "Lelah Dushkin":
                return "Lelah Dushkin";
                break;
            case "Leonard Young (via Marc Johnson)":
                return "Leonard Young";
                break;
            case "Leonora Hering (1898-1983)":
                return "Leonora Hering";
                break;
            case "Libraries Admin Office":
                return "University Libraries, Administrative Services";
                break;
            case "Libraries, Nelda Elder":
                return "University Libraries";
                break;
            case "Library of Congress":
                return "Library of Congress";
                break;
            case "Lillian Kramer (via Alex B. Stone)":
                return "Lillian Kramer";
                break;
            case "Linda M. Klabunde":
                return "Linda M. Klabunde";
                break;
            case "Linda Morse":
                return "Linda Morse";
                break;
            case "Little Snake River Museum":
                return "Little Snake River Museum";
                break;
            case "Lloyd Zimmer Books":
                return "Lloyd Zimmer, Books and Maps";
                break;
            case "Lloyd Zimmer, dealer":
                return "Lloyd Zimmer, Books and Maps";
                break;
            case "Lorena Meyer":
                return "Lorena Meyer";
                break;
            case "Lori Goetsch":
                return "Lori Goetsch";
                break;
            case "Lori L. Tolliver":
                return "Lori L. Tolliver";
                break;
            case "Louis S. Meyer":
                return "Louis S. Meyer";
                break;
            case "Louise Jernigan":
                return "Louise Jernigan";
                break;
            case "Louise Wheatley & George Wheatley Jr.":
                return "Louise Wheatley and George Wheatley Jr.";
                break;
            case "Luraine Collins Tansey":
                return "Luraine Collins Tansey";
                break;
            case "Lynelle Penner":
                return "Lynelle Penner";
                break;
            case "Mabel A. Murphy":
                return "Mabel A. Murphy";
                break;
            case "Macdonald Laboratory":
                return "James R. Macdonald Laboratory";
                break;
            case "Madeleine Rusk":
                return "Madeleine Rusk";
                break;
            case "Mae Gelman Danforth":
                return "Mae Gelman Danforth";
                break;
            case "Marc Chapman":
                return "Marc Chapman";
                break;
            case "Margo Kren":
                return "Margo Kren";
                break;
            case "Marie R. Bonebrake":
                return "Marie R. Bonebrake";
                break;
            case "Marion Boydston":
                return "Marion Boydston";
                break;
            case "Marion Pelton":
                return "Marion Pelton";
                break;
            case "Marjorie J. Morse":
                return "Marjorie J. Morse";
                break;
            case "Marjorie Shields":
                return "Marjorie Shields";
                break;
            case "Marjorie Steerman":
                return "Marjorie Steerman";
                break;
            case "Mark Hollis":
                return "Mark Hollis";
                break;
            case "Mark Schrock":
                return "Mark Schrock";
                break;
            case "Marlin Fitzwater":
                return "Marlin Fitzwater";
                break;
            case "Marshall Ann Bourgeois":
                return "Marshall Ann Bourgeois";
                break;
            case "Martha Streeter":
                return "Martha Streeter";
                break;
            case "Mary Ellen Sutton":
                return "Mary Ellen Sutton";
                break;
            case "Mary Frances Reed":
                return "Mary Frances Reed";
                break;
            case "Mary Helm Pollack":
                return "Mary Helm Pollack";
                break;
            case "Mary Jo Everett":
                return "Mary Jo Everett";
                break;
            case "Mary Kovar Poshak":
                return "Mary Kovar Poshak";
                break;
            case "Mary Lu Schwarz Lockhart":
                return "Mary Lu Schwarz Lockhart";
                break;
            case "Mary May":
                return "Mary May";
                break;
            case "Mary Radnor":
                return "Mary Radnor";
                break;
            case "Master Farmers":
                return "Master Farmers";
                break;
            case "Mathematics":
                return "Department of Mathematics";
                break;
            case "Mathematics  (Louis Pigno)":
                return "Department of Mathematics";
                break;
            case "Mathematics (Louis Pigno)":
                return "Department of Mathematics";
                break;
            case "Mathematics Department":
                return "Department of Mathematics";
                break;
            case "Mathematics Dept":
                return "Department of Mathematics";
                break;
            case "Max Milbourn":
                return "Max Milbourn";
                break;
            case "Max Miller Estate":
                return "Max Miller Estate";
                break;
            case "McCain Auditorium":
                return "McCain Auditorium";
                break;
            case "McManis":
                return "Geraldine McManis";
                break;
            case "McNair Scholars Program":
                return "McNair Scholars Program";
                break;
            case "McPherson County Old Mill Museum":
                return "McPherson County Old Mill Museum";
                break;
            case "Media Relations (News Services); Jan Hedrick":
                return "Media Relations and Marketing";
                break;
            case "Media Relations and Marketing (Cheryl May)":
                return "Media Relations and Marketing";
                break;
            case "Media Relations/Photo Services":
                return "News Services";
                break;
            case "Melia Erin Fritch":
                return "Melia Erin Fritch";
                break;
            case "Meredith McCaughey":
                return "Meredith McCaughey";
                break;
            case "Michael Brown Rare Books":
                return "Michael Brown Rare Books";
                break;
            case "Michael Haddock":
                return "Michael Haddock";
                break;
            case "Michael L. Donnelly":
                return "Michael L. Donnelly";
                break;
            case "Michael Suleiman":
                return "Michael Suleiman";
                break;
            case "Michaeline Chance-Reay":
                return "Michaeline Chance-Reay";
                break;
            case "Michaeline Chance-Reay, College of Education":
                return "Michaeline Chance-Reay";
                break;
            case "Mike Haddock":
                return "Mike Haddock";
                break;
            case "Mildred Crowl Martin":
                return "Mildred Crowl Martin";
                break;
            case "Miles McKee":
                return "Miles McKee";
                break;
            case "Military Science  (Army)":
                return "Department of Military Science";
                break;
            case "Military Science  (Army)":
                return "Department of Military Science";
                break;
            case "Military ScienceÂ  (Army)":
                return "Department of Military Science";
                break;
            case "Mortar Board":
                return "Mortar Board";
                break;
            case "Motor Voters":
                return "Motor Voters";
                break;
            case "Mr. & Mrs. D. E. Eshbaugh":
                return "Delbert E. and Irene K. Eshbaugh";
                break;
            case "Mr. & Mrs. Frank D. Ruppert":
                return "Frank D. and Reta Ruppert";
                break;
            case "Mrs. E. L. Holton":
                return "Lillian Sarah (Beck) Holton";
                break;
            case "Mrs. Howard Bradley":
                return "Eunice L. Bradley";
                break;
            case "Mrs. Mary Helm Pollack":
                return "Mary Helm Pollack";
                break;
            case "Myrna Bartel":
                return "Myrna Bartel";
                break;
            case "NACADA":
                return "National Academic Advising Association (NACADA)";
                break;
            case "NACADA (National Academic Advising Association)":
                return "National Academic Advising Association (NACADA)";
                break;
            case "Nancy Duran":
                return "Nancy Duran";
                break;
            case "Nancy Hawkins":
                return "Nancy Hawkins";
                break;
            case "Nancy Morse":
                return "Nancy Morse";
                break;
            case "Nancy Ryan":
                return "Nancy Ryan";
                break;
            case "National Consumer Law Center":
                return "National Consumer Law Center";
                break;
            case "Nelda Elder":
                return "Nelda Elder";
                break;
            case "Neoma (Hart) Youtsey":
                return "Neoma (Hart) Youtsey";
                break;
            case "New Services":
                return "Media Relations and Marketing";
                break;
            case "New Student Programs (M. Trotter)":
                return "Office of New Student Programs";
                break;
            case "News and Editorial Services, Division of Communications and Marketing":
                return "News and Editorial Services, Division of Communications and Marketing";
                break;
            case "News Services":
                return "News Services (1993-1994), News Media Services (2011)";
                break;
            case "News Services (Media Relations)":
                return "Media Relations and Marketing";
                break;
            case "Nicole Dawsen":
                return "Nicole Dawsen";
                break;
            case "Noel Schulz":
                return "Noel Schulz";
                break;
            case "None":
                return "None";
                break;
            case "Norman J. Fedder":
                return "Norman J. Fedder";
                break;
            case "Norman O. Forness":
                return "Norman O. Forness";
                break;
            case "North Dakota, Univ. of: Special Collections, Fritz Library":
                return "Elwyn B. Robinson Department of Special Collections, Chester Fritz Library, University of North Dakota";
                break;
            case "Novelists, Inc.":
                return "Novelists, Inc.";
                break;
            case "Office of Academic Personnel":
                return "Office of Academic Personnel";
                break;
            case "Office of Academic Personnel: Melissa Linenberger":
                return "Office of Academic Personnel";
                break;
            case "Office of Academic Personnel: Susana Valdovinos":
                return "Office of Academic Personnel";
                break;
            case "Office of Assessment: Linda Lake":
                return "Office of Assessment";
                break;
            case "Office of Diversity":
                return "Office of Diversity";
                break;
            case "Office of Faculty Senate":
                return "Faculty Senate";
                break;
            case "Office of Internal Audit":
                return "Office of Internal Audit";
                break;
            case "Office of International Programs":
                return "Office of International Programs";
                break;
            case "Office of Military Affairs":
                return "Office of Military Affairs";
                break;
            case "Office of Public Relations and Communication":
                return "Office of Public Relations and Communication";
                break;
            case "Office of Student Activities and Services":
                return "Office of Student Activities and Services";
                break;
            case "Office of Student Activities and Services: Student Governing Association":
                return "Student Governing Association";
                break;
            case "Office of Student Life":
                return "Office of Student Life";
                break;
            case "Office of the President":
                return "Office of the President";
                break;
            case "Office of the President: Dana Hastings [Anderson attic]":
                return "Office of the President";
                break;
            case "Office of the Provost":
                return "Office of the Provost";
                break;
            case "Office of the Provost and Senior Vice President":
                return "Office of the Provost and Senior Vice President";
                break;
            case "Office of the Vice President for Student Life":
                return "Office of the Vice President for Student Life";
                break;
            case "Office of Vice President for Research":
                return "Office of Vice President for Research";
                break;
            case "Orville Schwanke":
                return "Orville Schwanke";
                break;
            case "Orville W. Bidwell":
                return "Orville W. Bidwell";
                break;
            case "Osage City Public Library":
                return "Osage City Public Library";
                break;
            case "Oscar E. Olin":
                return "Oscar E. Olin";
                break;
            case "Oscar Larmer":
                return "Oscar Larmer";
                break;
            case "Palace, Phyllis Pease":
                return "Phyllis Pease";
                break;
            case "Pat Bosco":
                return "Dean of Students";
                break;
            case "Pat Hartman":
                return "Pat Hartman";
                break;
            case "Pat Patton":
                return "Patricia A. Patton";
                break;
            case "Patrice Lewerenz":
                return "Patrice Lewerenz";
                break;
            case "Patricia A. Patton":
                return "Patricia A. Patton";
                break;
            case "Patricia J. O’Brien":
                return "Patricia J. O'Brien";
                break;
            case "Patrick Lee":
                return "Patrick Lee";
                break;
            case "Paul D. Ohlenbusch, Extension Agronomy":
                return "Paul D. Ohlenbusch";
                break;
            case "Paul Sanford":
                return "Paul Sanford";
                break;
            case "Paul Young":
                return "Paul Young";
                break;
            case "Payroll":
                return "Human Resource Services";
                break;
            case "Pearce Keller American Legion Post 17":
                return "Pearce-Keller Post No. 17, The American Legion";
                break;
            case "Peg Wherry":
                return "Peg Wherry";
                break;
            case "Personnel":
                return "Personnel Services";
                break;
            case "Personnel Services":
                return "Personnel Services";
                break;
            case "Phi Beta Kappa":
                return "Phi Beta Kappa, Beta of Kansas chapter";
                break;
            case "Phi Kappa Phi":
                return "Phi Kappa Phi";
                break;
            case "Philip Nel":
                return "Philip Nel";
                break;
            case "Phillip F. Schlee":
                return "Phillip F. Schlee";
                break;
            case "Philosophy":
                return "Department of Philosophy";
                break;
            case "Photo Services":
                return "Photographic Services";
                break;
            case "Photo Services (D. Donnert)":
                return "Photographic Services";
                break;
            case "Photographic Services":
                return "Photographic Services";
                break;
            case "Photographic Services (Dan Donnart)":
                return "Photographic Services";
                break;
            case "Phylis Rowe":
                return "Phyllis Rowe";
                break;
            case "Phyllis Chesbro":
                return "Phyllis Chesbro";
                break;
            case "Physical Limitations":
                return "Services for Students with Physical Limitations";
                break;
            case "Physics":
                return "Department of Physics";
                break;
            case "Physics Department":
                return "Department of Physics";
                break;
            case "Plant Pathology":
                return "Department of Plant Pathology";
                break;
            case "Political Science Dept.":
                return "Department of Political Science";
                break;
            case "Polly Torrence":
                return "Polly Torrence";
                break;
            case "Presbyterian Historical Society":
                return "Presbyterian Historical Society";
                break;
            case "President, Office of":
                return "Office of the President";
                break;
            case "President, Office of the":
                return "Office of the President";
                break;
            case "President, Office of the (Jon Wefald)":
                return "Office of the President";
                break;
            case "President, Office of the (Lynne Lundberg)":
                return "Office of the President";
                break;
            case "President’s office":
                return "Office of the President";
                break;
            case "President’s Office":
                return "Office of the President";
                break;
            case "President’s Office: Dana Hastings":
                return "Office of the President";
                break;
            case "President's Office":
                return "Office of the President";
                break;
            case "Printing Services.":
                return "Printing Services";
                break;
            case "Professor Charles Gardner Shaw":
                return "Charles Gardner Shaw";
                break;
            case "Provost, Office of the":
                return "Office of the Provost";
                break;
            case "Provost, Office of the (through library reserves)":
                return "Office of the Provost";
                break;
            case "Psychology":
                return "Department of Psychology";
                break;
            case "Pub. Rel. & Comm. (Cy Wainscott)":
                return "News and Information";
                break;
            case "Public Safety (Lezlie Schinstock)":
                return "Department of Public Safety";
                break;
            case "Public Safety, Department of":
                return "Division of Public Safety";
                break;
            case "Purchase":
                return "Purchase";
                break;
            case "Purchase with NEH funds, Howard Karno Books":
                return "Howard Karno Books";
                break;
            case "Purchase, Howard Karno Books":
                return "Howard Karno Books";
                break;
            case "Purchase, Lloyd Zimmer Book and Maps":
                return "Lloyd Zimmer, Books and Maps";
                break;
            case "Purchased":
                return "Purchase";
                break;
            case "Putnam Hall":
                return "Putnam Hall";
                break;
            case "Quentin Jared":
                return "Quentin Jared";
                break;
            case "R. L. D. Morse":
                return "Richard L. D. Morse";
                break;
            case "Ralph Lipper (via Mike Haddock, KSU Libraries)":
                return "Ralph Lipper";
                break;
            case "Ralph Miller via daughter Kathy Miller":
                return "Ralph Miller";
                break;
            case "Ralph O. Willard":
                return "Ralph O. Willard";
                break;
            case "Ralph Sparks":
                return "Ralph Sparks";
                break;
            case "Ralph Titus":
                return "Ralph Titus";
                break;
            case "Ray Terry":
                return "Ray Terry";
                break;
            case "Ray Weisenburger, College of Architecture":
                return "Ray Weisenburger";
                break;
            case "Rebecca L. Coan, 1948":
                return "Rebecca L. Coan";
                break;
            case "Recreational Services":
                return "Recreational Services";
                break;
            case "Reed Hoffman":
                return "Reed Hoffman";
                break;
            case "Registrar":
                return "Office of the Registrar";
                break;
            case "Registrar via Facilities (Recycling)":
                return "Office of the Registrar";
                break;
            case "Religious Affairs":
                return "Office of Religious Affairs";
                break;
            case "Research and Extension: Dept. of Communications; Jennifer Alexander (9-18-08)":
                return "Kansas State Research and Extension";
                break;
            case "Retta Mae Woodward":
                return "Retta Mae Woodward";
                break;
            case "Philip K. Reynolds":
                return "Philip K. Reynolds";
            case "Reynolds book dealer":
                return "Philip K. Reynolds";
                break;
            case "Richard (Dick) Haines":
                return "Richard Haines";
                break;
            case "Richard A. Lill":
                return "Richard A. Lill";
                break;
            case "Richard B. Myers":
                return "Richard B. Myers";
                break;
            case "Richard B. Myers via KSU Foundation":
                return "Richard B. Myers";
                break;
            case "Richard Coleman":
                return "Richard P. Coleman";
                break;
            case "Richard D. Rees":
                return "Richard D. Rees";
                break;
            case "Richard Haines":
                return "Richard Haines";
                break;
            case "Richard J. Seitz":
                return "Richard J. Seitz";
                break;
            case "Richard L. D. Morse":
                return "Richard L. D. Morse";
                break;
            case "Richard L. Vanderlip":
                return "Richard L. Vanderlip";
                break;
            case "Richard Shields, Installation Restoration Prog. Ft. Riley":
                return "Installation Restoration Program, Fort Riley";
                break;
            case "Richter":
                return "William Richter";
                break;
            case "Riley County Historical Soc.":
                return "Riley County Historical Society";
                break;
            case "Riley County Historical Society":
                return "Riley County Historical Society";
                break;
            case "Robert A. Felde":
                return "Robert A. Felde";
                break;
            case "Robert Aldrine":
                return "Robert Aldrine";
                break;
            case "Robert H. Berlin":
                return "Robert H. Berlin";
                break;
            case "Robert K. Nabours":
                return "Robert K. Nabours";
                break;
            case "Robert Krause/K-State Athletic Dept.":
                return "K-State Athletics";
                break;
            case "Robert Kruh":
                return "Robert Kruh";
                break;
            case "Robert McEwen":
                return "Robert McEwen";
                break;
            case "Robert Reeves":
                return "Robert Reeves";
                break;
            case "Robert Simonsen":
                return "Robert Simonsen";
                break;
            case "Robert V. Ratts":
                return "Robert V. Ratts";
                break;
            case "Robert W. Patterson":
                return "Robert W. Patterson";
                break;
            case "Robert W. Schoeff":
                return "Robert W. Schoeff";
                break;
            case "Robin Higham":
                return "Robin Higham";
                break;
            case "Roderic Simpson":
                return "Roderic Simpson";
                break;
            case "Roe Borsdorf, Food & Feed Grains Institute":
                return "Food and Feed Grains Institute";
                break;
            case "Roger Adams":
                return "Roger C. Adams";
                break;
            case "Rosamond Haeberle (Albert P. Haeberle)":
                return "Rosamond P. Haeberle";
                break;
            case "Rosella Ogg via Nelda Elder":
                return "Rosella Ogg";
                break;
            case "Roy & Mary Page":
                return "Roy and Mary Page";
                break;
            case "Roy and Joan Ruzika":
                return "Roy and Joan Ruzika";
                break;
            case "Roy Kiesling":
                return "Roy Kiesling";
                break;
            case "Ruth Ann Wefald":
                return "Ruth Ann Wefald";
                break;
            case "Ruth Hoeflin":
                return "Ruth Hoeflin";
                break;
            case "Ruth Hoeflin (Margaret Nordin)":
                return "Ruth Hoeflin";
                break;
            case "Ruth Milbourn":
                return "Ruth Milbourn";
                break;
            case "Sandra Aberer":
                return "Sandra Aberer";
                break;
            case "Sandra Graham":
                return "Sandra Graham";
                break;
            case "School of Music, Theatre, and Dance":
                return "School of Music, Theatre, and Dance";
                break;
            case "Scott Smith":
                return "Scott Smith";
                break;
            case "Sesquicentennial Steering Committee":
                return "Sesquicentennial Steering Committee";
                break;
            case "Shalia Estes":
                return "Shalia Estes";
                break;
            case "Sharon Barnard":
                return "Sharon Barnard";
                break;
            case "Sharon Eisenberg":
                return "Sharon Eisenberg";
                break;
            case "Sharon Molzen":
                return "Sharon Molzen";
                break;
            case "Sharon Morrow":
                return "Sharon Morrow";
                break;
            case "Sheep and Goat Meat Center":
                return "Sheep and Goat Meat Center";
                break;
            case "Sherry Rabbino Lewis":
                return "Sherry Rabbino Lewis";
                break;
            case "Shiloh Dutton":
                return "Shiloh Dutton";
                break;
            case "Sidney H. Willner":
                return "Sidney H. Willner";
                break;
            case "Sigma Xi":
                return "Sigma Xi, Kansas State University chapter";
                break;
            case "Society for Military History":
                return "Society for Military History";
                break;
            case "Sociology, Anthropology & Social Work":
                return "Department of Sociology, Anthropology, and Social Work";
                break;
            case "Sports Information":
                return "Sports Information";
                break;
            case "Staley School of Leadership Studies":
                return "Staley School of Leadership Studies";
                break;
            case "Stephen L. Stover":
                return "Stephen L. Stover";
                break;
            case "Stephen R. Bollman":
                return "Stephen R. Bollman";
                break;
            case "Steve Galitzer":
                return "Environmental Health and Safety";
                break;
            case "Steve Graham":
                return "International Agricultural Programs";
                break;
            case "Stewart Lee":
                return "Stewart Lee";
                break;
            case "Student Activities":
                return "Office of Student Activities and Services";
                break;
            case "Student Development (C. Peine)":
                return "Center for Student Development";
                break;
            case "Student Governing Association":
                return "Student Governing Association";
                break;
            case "Student Life, Dean of":
                return "Office of Student Life";
                break;
            case "Student Publications":
                return "Student Publications";
                break;
            case "Student Publications (Gary Lytle)":
                return "Student Publications";
                break;
            case "Students, Asst. Dean of (Caroline Peine)":
                return "Office of Student Life";
                break;
            case "Student's, Asst. Dean of (Caroline Peine)":
                return "Office of Student Life";
                break;
            case "Students, Dean of (Caroline Peine)":
                return "Office of Student Life";
                break;
            case "Sue Dawson":
                return "Sue Dawson";
                break;
            case "Sue Maes":
                return "Sue Maes";
                break;
            case "Sue Peterson":
                return "Sue Peterson";
                break;
            case "Sylvia Blanding":
                return "Sylvia Blanding";
                break;
            case "Tamara Compton":
                return "Tamara Compton";
                break;
            case "Teaching and Learning Center":
                return "Teaching and Learning Center";
                break;
            case "Ted and Elaine Phillips":
                return "Ted and Elaine Phillips";
                break;
            case "Tessie Agan":
                return "Tessie Agan";
                break;
            case "The University of Montana":
                return "The University of Montana";
                break;
            case "Theta Iota chapter of Delta Delta Delta":
                return "Delta Delta Delta, Theta Iota chapter";
                break;
            case "Thomas Brooks":
                return "Thomas Brooks";
                break;
            case "Tim Lindemuth":
                return "Tim Lindemuth";
                break;
            case "Tina Marie Winters":
                return "Tina Marie Winters";
                break;
            case "Toledo-Lucas County Public Library, Toledo OH":
                return "Toledo-Lucas County Public Library";
                break;
            case "Tom McCahon":
                return "Tom McCahon";
                break;
            case "Tom Rawson, Vice President for Administration & Finance":
                return "Vice President for Administration and Finance";
                break;
            case "Tony Crawford":
                return "Anthony R. Crawford";
                break;
            case "Tsutomu Ohno":
                return "Tsutomu Ohno";
                break;
            case "UFM":
                return "UFM";
                break;
            case "Undergraduate Grievance Bd. (C.L. Dehon, Chair)":
                return "Undergraduate Grievance Committee";
                break;
            case "Undergraduate Grievance Comm. (Claire Dehon, Chair)":
                return "Undergraduate Grievance Committee";
                break;
            case "United States Commission on Military History":
                return "United States Commission on Military History";
                break;
            case "Univ of Kansas, Kansas Coll.":
                return "University of Kansas Libraries, Kenneth Spencer Research Library, Kansas Collection";
                break;
            case "University Archives":
                return "University Archives";
                break;
            case "University Archives, Emporia State University":
                return "Emporia State University Libraries, University Archives";
                break;
            case "University Attorney":
                return "University Attorney";
                break;
            case "University Facilities":
                return "Division of University Facilities";
                break;
            case "University for Man":
                return "UFM";
                break;
            case "University for Man (Antonia Pigno)":
                return "UFM";
                break;
            case "University Libraries":
                return "University Libraries";
                break;
            case "University of Kansas":
                return "University of Kansas";
                break;
            case "University Printing":
                return "University Printing";
                break;
            case "University Printing (part of Ag’s Dept of Comm)":
                return "University Printing";
                break;
            case "University Publications":
                return "Publications Services";
                break;
            case "University Publications (Cindy Bogue)":
                return "University Publications";
                break;
            case "University Relations (C. Rochat)":
                return "Office of University Relations";
                break;
            case "University Relations (Charles Hein)":
                return "Office of University Relations";
                break;
            case "University Relations (Cheryl May)":
                return "News and Information";
                break;
            case "University Relations (Cy Wainscott)":
                return "Office of University Relations";
                break;
            case "University Relations (N. Ross)":
                return "Office of University Relations";
                break;
            case "University Relations (Rochat)":
                return "Office of University Relations";
                break;
            case "University Relations (Tim Lindemuth)":
                return "Office of Public Relations and Communication";
                break;
            case "Unknown":
                return "Unknown";
                break;
            case "Unknown (mailed with Leavenworth postmark)":
                return "Unknown";
                break;
            case "US Dept. of the Interior, National Park Service":
                return "U.S. Department of the Interior, National Park Service";
                break;
            case "Various":
                return "Various";
                break;
            case "Veryl Switzer":
                return "Veryl Switzer";
                break;
            case "Vet Med":
                return "College of Veterinary Medicine";
                break;
            case "Vet Med (Howard Erickson)":
                return "College of Veterinary Medicine";
                break;
            case "Vice President for Administration and Finance":
                return "Vice President for Administration and Finance";
                break;
            case "Vice President for Institutional Advancement (Robert Krause)":
                return "Vice President for Institutional Advancement";
                break;
            case "Vice President for Student Life":
                return "Vice President for Student Life";
                break;
            case "Vice President of Student Affairs":
                return "Vice President of Student Affairs";
                break;
            case "Vice Provost for Academic Services and Technology":
                return "Vice Provost for Academic Services and Technology";
                break;
            case "Vice-Provost, Office of the (Robert Kruh)":
                return "Office of the Vice-Provost";
                break;
            case "Virginia Houser":
                return "Virginia Houser";
                break;
            case "Virginia Knauer":
                return "Virginia Knauer";
                break;
            case "Virginia Moxley":
                return "Virginia Moxley";
                break;
            case "Virnelle Y. Jones Fletcher":
                return "Virnelle Y. Jones Fletcher";
                break;
            case "VP Tom Rawson’s office":
                return "Vice President for Administration and Finance";
                break;
            case "W. M. Stingley":
                return "Walter M. Stingley";
                break;
            case "W.G. Hole":
                return "W.G. Hole";
                break;
            case "Walter and Francis Lewis":
                return "Walter and Francis Lewis";
                break;
            case "Walter Cash":
                return "Walter Cash";
                break;
            case "Walter T. Dartland":
                return "Walter T. Dartland";
                break;
            case "Washburn University":
                return "Washburn University";
                break;
            case "Watermark West, purchase":
                return "Watermark West Rare Books";
                break;
            case "Wava Skaggs":
                return "Wava Skaggs";
                break;
            case "Wayne Rogler":
                return "Wayne Rogler";
                break;
            case "Wayne Wagner":
                return "Wayne Wagner";
                break;
            case "Wendell Hockens":
                return "Wendell Hockens";
                break;
            case "Wendell Lady":
                return "Wendell Lady";
                break;
            case "Wheatland Antiques (Mrs. H. P. Boles)":
                return "Wheatland Antiques";
                break;
            case "Wheatley, Alison":
                return "Alison Wheatley";
                break;
            case "William H. Avery":
                return "William H. Avery";
                break;
            case "William Koch":
                return "William Koch";
                break;
            case "William Mackenstadt":
                return "William Mackenstadt";
                break;
            case "William McKale":
                return "William McKale";
                break;
            case "William Muir":
                return "William Muir";
                break;
            case "William P. Morrison":
                return "William P. Morrison";
                break;
            case "William Richter":
                return "William Richter";
                break;
            case "William Richter, Dept. of Political Science":
                return "Department of Political Science";
                break;
            case "William Richter, International Programs":
                return "Office of International Programs";
                break;
            case "William Stamey":
                return "William Stamey";
                break;
            case "Zoe Climenhaga":
                return "Zoe Climenhaga";
                break;
            default:
                return "***LOOKAT***";
                break;
        }
    }

    protected function parseAccessionNumberForYear($accessionNumber)
    {
        // regex to pull out year from accession number
        $regexPattern = "/(\d){4}/";
        preg_match($regexPattern, $accessionNumber, $matches);
        return $matches[0];

    }
}
