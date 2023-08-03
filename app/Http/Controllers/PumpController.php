<?php


namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class PumpController extends Controller
{
    public function pumpcsv($date)
    {
        // Convert date format to 'YYYY-MM-DD'
        $formattedDate = date('Y-m-d', strtotime($date));

        $sqlQuery = "
        SELECT 
        q1.dt_time,
        q1.Client_Name,
        q1.Client_Id,
        q1.Device_Name,
        q1.Device_Id,
        CAST(ROUND((q1.max_wh_R - COALESCE(q1.min_wh_R, 0)) / 1000, 0) AS DECIMAL(10, 0)) AS today_kwh,
        CAST(ROUND((q1.min_wh_R - COALESCE(q2.min_wh_R, 0)) / 1000, 0) AS DECIMAL(10, 0)) AS yesterday_kwh, 
        q5.wh_D  AS flow,
	 q5.wh_2  AS pressure,
	 q5.wh_D * 0.27778 * q5.wh_2 * 100 AS output,
         q5.dt_time AS dt_time2
    FROM (
        SELECT 
            T1.dt_time,
            T2.client_name AS Client_Name,
            T2.device_id AS Device_Id,
            T2.client_id AS Client_Id,
            T2.device_name AS Device_Name,
            T2.pump AS pump,
            MAX(T1.max_wh_R) AS max_wh_R,
            MAX(T1.min_wh_R) AS min_wh_R
        FROM (
            SELECT 
                MAX(wh_R) AS max_wh_R,
                MIN(wh_R) AS min_wh_R,
                MAX(dt_time) AS dt_time,
                host,
                device_id
            FROM jnmc_all_kwh
            WHERE DATE(dt_time) = ?
            GROUP BY host, device_id
        ) T1
        INNER JOIN device_details_wardha T2 ON T1.host = T2.client_id AND T1.device_id = T2.device_id
        WHERE T2.pump = 1
        GROUP BY T1.dt_time, T2.client_name, T2.client_id, T2.device_name, T2.device_id, T2.pump -- Group by non-aggregated columns
    ) q1
    LEFT JOIN (
        SELECT 
            MAX(wh_R) AS max_wh_R,
            MIN(wh_R) AS min_wh_R,
            MIN(wh_D) AS min_wh_D,
            device_id,
            host
        FROM jnmc_all_kwh
        WHERE DATE(dt_time) = DATE(?) - INTERVAL 1 DAY
        GROUP BY device_id, host
    ) q2 ON q1.Device_Id = q2.device_id AND q1.Client_Id = q2.host
    LEFT JOIN (
        SELECT 
            MAX(wh_R) AS max_wh_R_prev_day,
            MIN(wh_R) AS min_wh_R_prev_day,
            device_id,
            host
        FROM jnmc_all_kwh
        WHERE DATE(dt_time) = DATE(?) - INTERVAL 2 DAY
        GROUP BY device_id, host
    ) q4 ON q1.Device_Id = q4.device_id AND q1.Client_Id = q4.host
    LEFT JOIN (
    SELECT K1.dt_time, K1.wh_R, K1.wh_D, K1.wh_2, D2.client_id, D2.device_id
    FROM jnmc_all_kwh K1
    JOIN device_details_wardha D2 ON K1.device_id = D2.device_id AND K1.host = D2.client_id
    JOIN (
        SELECT device_id, host, MAX(dt_time) AS max_dt_time
        FROM jnmc_all_kwh
        WHERE DATE(dt_time) = ?
        GROUP BY device_id, host
    ) AS max_times ON K1.device_id = max_times.device_id AND K1.host = max_times.host AND K1.dt_time = max_times.max_dt_time
    WHERE DATE(K1.dt_time) = ? AND D2.pump = 1) q5 ON q4.Device_Id = q5.device_id AND q4.host = q5.Client_Id
    ORDER BY q1.Client_Name, q1.Device_Name;";

        $sqlData = DB::select($sqlQuery, [$formattedDate, $formattedDate, $formattedDate, $formattedDate, $formattedDate]);

        // var_dump($sqlData);

        // Extract folders and IDs from SQL data
        $folders = [];
        $ids = [];
        foreach ($sqlData as $row) {
            $folders[] = $row->Client_Id;
            $ids[] = $row->Device_Id;
        }

       
        $filteredData = [];

        foreach ($folders as $folder) {
            $csvPaths = [
                "F:/wardha/demowebsite/public/$folder/{$date}_{$folder}.csv",
                "F:/wardha/demowebsite/public/folder2/{$date}_folder2.csv",
                // Add more CSV paths here
            ];
    
            foreach ($csvPaths as $csvPath) {
                $currentCsvPath = str_replace(['$folder', '{$date}'], [$folder, $date], $csvPath);

                if (file_exists($currentCsvPath)) {
                    $data = array_map('str_getcsv', file($currentCsvPath));

                    foreach ($ids as $id) {
                        $filteredRows = array_filter($data, function ($row) use ($id) {
                            return isset($row[2]) && $row[2] == $id;
                        });

                        $lastRow = end($filteredRows);
                        if ($lastRow) {
                            $filteredData[] = array_slice($lastRow, 0, 5);
                        }
                    }
                }
            }
        }

        // Remove duplicates from $filteredData
        $filteredData = array_map("unserialize", array_unique(array_map("serialize", $filteredData)));

        // Combine CSV and SQL data
        $combinedData = [];

        foreach ($filteredData as $csvRow) {
            $csvClientId = $csvRow[1];
            $csvDeviceId = $csvRow[2];

            foreach ($sqlData as $sqlRow) {
                $sqlClientId = $sqlRow->Client_Id;
                $sqlDeviceId = $sqlRow->Device_Id;

                if ($csvClientId === $sqlClientId && (int) $csvDeviceId === $sqlDeviceId) {
                    $combinedRow = array_merge($csvRow, (array) $sqlRow);

                    // Add the "input" column to the combined row
                    $csvFifthColumn = isset($csvRow[4]) ? (float) $csvRow[4] : null;
                    $input = !is_null($csvFifthColumn) ? round($csvFifthColumn, 3) : null;
                    $combinedRow['input'] = $input; // Set the key as 'input' with the calculated value

                    // Calculate the efficiency column
                    $output = isset($combinedRow['output']) ? (float) $combinedRow['output'] : null;
            
                    // Avoid division by zero error
                    $efficiency = !is_null($input) && !is_null($output) && $input != 0 ? round(($output / $input) * 100, 2) : null;
            
                    $combinedRow['efficiency'] = $efficiency;

                    $combinedData[] = $combinedRow;
                    break; // Break the inner loop after finding a match
                }
            }
        }

        return response()->json($combinedData);
    }
}