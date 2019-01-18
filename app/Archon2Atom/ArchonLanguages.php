<?php

/*
 *
 * The file is specific to KSU where we are only doing data conversation of the
 * language code for languages that we are using. Also generating the language
 * file from a local copy of the database
*/

namespace App\Archon2Atom;

use Illuminate\Support\Facades\DB;

class ArchonLanguages
{

    protected $archonLanguages;

    public function __construct()
    {
        $resultingData = $this->processData();
    }

    public function processData()
    {
        $collectionLanguages = DB::table('tblCollections_CollectionLanguageIndex')->get();


        foreach ($collectionLanguages as $collection) {
            $collections[$collection->CollectionID][] = $this->langugeMapping($collection->LanguageID);
        }

        foreach ($collections as $id => $collection2) {
            $this->archonLanguages[$id]['collectionID'] = $id;
            $this->archonLanguages[$id]['languages'] = implode("|", $collection2);
        }
    }


    public function getLanguagesForCollectionID($collectionID)
    {
        if (array_key_exists($collectionID, $this->archonLanguages)) {
            return $this->archonLanguages[$collectionID]['languages'];
        } else {
            return '';
        }
    }

    protected function langugeMapping($languageID)
    {
        switch ($languageID) {
            case 1968:
                // Arabic -ara -- ar
                return 'ar';
                break;
            case 2051:
                // Creolesandpidgins,French-based(Other) - cpf -- cr
                return 'crp';
                break;
            case 2061:
                // Danish - dan -- da
                return 'da';
                break;
            case 2081:
                // English - eng -- en
                return 'en';
                break;
            case 2099:
                // French - Fre -- fr
                return 'fr';
                break;
            case 2114:
                // German - ger -- de
                return 'de';
                break;
            case 2134:
                // Hebrew - heb -- he
                return 'he';
                break;
            case 2330:
                // Russian - rus -- ru
                return 'ru';
                break;
            case 2372:
                // Spanish;Castilian - spa -- es
                return 'es';
                break;
            default:
                return '';
                break;
        }
    }
}
