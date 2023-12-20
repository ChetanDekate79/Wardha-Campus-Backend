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

        // foreach ($csvPaths as $csvPath) {
        //     if (file_exists($csvPath)) {
        //         $data = array_map('str_getcsv', file($csvPath));
        //         $filteredData = array_merge($filteredData, array_filter($data, function ($row) use ($id) {
        //             return isset($row[2]) && $row[2] == $id;
        //         }));
        //     }
        // }
        // return response()->json(array_values($filteredData));

        foreach ($csvPaths as $csvPath) {
            if (file_exists($csvPath)) {
                $data = array_map('str_getcsv', file($csvPath));
                foreach ($data as $row) {
                    if (isset($row[2]) && $row[2] == $id) {
                        // Divide specific columns (e.g., column 3 and column 4) by 1000
                        $row[4] = $row[4] / 1000; $row[6] = $row[6] / 1000;
                        $row[5] = $row[5] / 1000; $row[7] = $row[7] / 1000;
                        $row[8] = $row[8] / 1000; $row[9] = $row[9] / 1000;
                        $row[10] = $row[10] / 1000; $row[11] = $row[11] / 1000; 
                        $row[16] = $row[16] / 1000; $row[17] = $row[17] / 1000;
                        $row[18] = $row[18] / 1000; $row[19] = $row[19] / 1000;
                        $row[33] = $row[33] / 1000;  $row[34] = $row[34] / 1000;
                        $row[35] = $row[35] / 1000; $row[36] = $row[36] / 1000;
                        $row[37] = $row[37] / 1000; $row[38] = $row[38] / 1000;
                        $row[39] = $row[39] / 1000; $row[40] = $row[40] / 1000;
                        $filteredData[] = $row;
                    }
                }
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
