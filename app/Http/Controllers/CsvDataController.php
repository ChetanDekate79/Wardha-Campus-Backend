<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use League\Csv\Reader;

class CsvDataController extends Controller
{
    public function index()
    {
        $filePath = 'F:/wardha/AV7/01-01-2023_AV7.csv';

        $csv = Reader::createFromPath($filePath, 'r');
        $csv->setHeaderOffset(0);

        $records = $csv->getRecords();
        $data = iterator_to_array($records);

        return response()->json($data);
    }

    public function getByFolderDateId($folder, $date, $id)
    {
        $csvPaths = [
            "F:/wardha/demowebsite/public/$folder/{$date}_{$folder}.csv",
            // "C:/Inetpub/vhosts/hetadatain.com/wardha.hetadatain.com/JNMC_2_copy/$folder/{$date}_{$folder}.csv",
            // "C:/Inetpub/vhosts/hetadatain.com/wardha.hetadatain.com/JNMC_3_copy/$folder/{$date}_{$folder}.csv"
        ];
        $filteredData = [];

        foreach ($csvPaths as $csvPath) {
            if (file_exists($csvPath)) {
                $data = array_map('str_getcsv', file($csvPath));
                $filteredData = array_merge($filteredData, array_filter($data, function ($row) use ($id) {
                    return isset($row[2]) && $row[2] == $id;
                }));
            }
        }
        return response()->json(array_values($filteredData));
    }

    public function csv_biller($folder, $date, $id)
    {
        $csvPatterns = ["{$date}_{$folder}-6435-mfd_data_*.csv", "{$date}_{$folder}-6435-mfd_data.csv"];
        $filteredData = [];
    
        foreach ($csvPatterns as $csvPattern) {
            $csvPath = "F:/wardha/demowebsite/public/biller/{$csvPattern}";
            $matchingFiles = glob($csvPath);
            
            foreach ($matchingFiles as $file) {
                $data = array_map('str_getcsv', file($file));
                $filteredData = array_merge($filteredData, array_filter($data, function ($row) use ($id) {
                    return isset($row[2]) && $row[2] == $id;
                }));
    
                // echo $file . "<br>"; // Print the CSV path
            }
        }
    
        return response()->json(array_values($filteredData));
    }
    
}
