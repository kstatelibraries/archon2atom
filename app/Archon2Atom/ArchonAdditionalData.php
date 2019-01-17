<?php

/*
 *
 * This file pulls out data that does not have an easy way to be mapped to
 * AtoM. Pulls data from a local copy of the Archon Database.
 *
*/

namespace App\Archon2Atom;

use Illuminate\Support\Facades\DB;
use League\Csv\Writer;

class ArchonAdditionalData
{
    public function __construct(...$columnsToSearch)
    {
        foreach ($columnsToSearch as $column)
        {
            $this->retreiveAdditionalDataByColumn($column);
        }
    }

    public function retreiveAdditionalDataByColumn($columnName)
    {
        $columnsToSelect = ['ID', 'Title', $columnName];
        $csvHeaders = ['CollectionID', 'Title', $columnName];
        $otherURLRecords = DB::table('tblCollections_Collections')->select($columnsToSelect)->where($columnName, '!=', 'NULL')->get();

        foreach($otherURLRecords as $record)
        {
            $data[] = collect($record)->toArray();
        }
        
        $this->exportData($csvHeaders, $data, $columnName . 'Data');
    }

    public function exportData($headers, $data, $fileName)
    {
        $filename = sprintf("%s.csv", $fileName);
        $writer = Writer::createFromPath('/home/vagrant/code/archon2atom/storage/app/data_import/nonmappedata/' . $filename, 'w+');
        $writer->insertOne($headers);
        $writer->insertAll($data);
    }
}
