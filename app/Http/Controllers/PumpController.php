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
                q1.device_category,
                CAST(ROUND((q1.max_wh_R - COALESCE(q1.min_wh_R, 0)) / 1000, 0) AS DECIMAL(10, 0)) AS today_kwh,
                CAST(ROUND((q1.min_wh_R - COALESCE(q2.min_wh_R, 0)) / 1000, 0) AS DECIMAL(10, 0)) AS yesterday_kwh
            FROM (
                SELECT 
                    T1.dt_time,
                    T2.client_name AS Client_Name,
                    T2.device_id AS Device_Id,
                    T2.client_id AS Client_Id,
                    T2.device_name AS Device_Name,
                    T2.pump AS pump,
                    T2.device_category,
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
                GROUP BY T1.dt_time, T2.client_name, T2.client_id, T2.device_name, T2.device_id, T2.pump, T2.device_category -- Group by non-aggregated columns
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
                WHERE DATE(K1.dt_time) =  ? AND D2.pump = 1
            ) q5 ON q1.Device_Id = q5.device_id AND q1.Client_Id = q5.Client_Id 
            LEFT JOIN (
                SELECT client_id, device_id, max(eff) as eff FROM efficiency WHERE DATE(dt_time) = ?
                GROUP BY client_id, device_id
            ) AS efficiency_table ON efficiency_table.Device_Id = q1.device_id AND efficiency_table.Client_Id = q1.client_id
            ORDER BY q1.Client_Name, q1.Device_Name;";

        $sqlData = DB::select($sqlQuery, [$formattedDate, $formattedDate, $formattedDate, $formattedDate, $formattedDate, $formattedDate]);

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
                            $filteredData[] = array_slice($lastRow, 0, 52);
                        }
                    }
                }
            }
        }

        $filteredData = array_map("unserialize", array_unique(array_map("serialize", $filteredData)));

        $combinedData = [];

        foreach ($filteredData as $csvRow) {
            $csvClientId = $csvRow[1];
            $csvDeviceId = $csvRow[2];
        
            foreach ($sqlData as $sqlRow) {
                $sqlClientId = $sqlRow->Client_Id;
                $sqlDeviceId = $sqlRow->Device_Id;
        
                if ($csvClientId === $sqlClientId && (int) $csvDeviceId === $sqlDeviceId) {
                    $combinedRow = array_merge($csvRow, (array) $sqlRow);
        
                    // Declare the $kw_csv variable
                    $kw_csv = null;
        
                    // Check if device_category is "pump"
                    $deviceCategory = $sqlRow->device_category;
                    if ($deviceCategory === 'pump') {
                        // Add the "kw_csv" column to the combined row
                        $csvFifthColumn = isset($csvRow[4]) ? (float) $csvRow[4] : null;
                        $kw_csv = !is_null($csvFifthColumn) ? round($csvFifthColumn, 3) : null;
                        $combinedRow['kw_csv'] = $kw_csv;
        
                        // Add the "flow_rate_csv" column to the combined row
                        $flowRateCsv = isset($csvRow[49]) ? (float) $csvRow[49] : null;
                        $combinedRow['flow_rate_csv'] = round($flowRateCsv, 2);
        
                        // Add the "pressure_csv" column to the combined row
                        $pressurecsv = isset($csvRow[51]) ? (float) $csvRow[51] : null;
                        $combinedRow['pressure_csv'] = $pressurecsv;
        
                        // Calculate the "flow_pressure" column
                        $flowPressure = !is_null($flowRateCsv) && !is_null($pressurecsv) ? ($flowRateCsv * 0.277 * $pressurecsv * 100) : null;
                        $combinedRow['flow_pressure'] = $flowPressure;
        
                        // Calculate the "pump_efficiency_csv" column
                        $pumpEfficiencyCsv = !is_null($flowPressure) && !is_null($kw_csv) && $kw_csv != 0
                            ? number_format(($flowPressure / $kw_csv) * 100, 1)
                            : null;
                        $combinedRow['pump_efficiency_csv'] = $pumpEfficiencyCsv;
                    } else {
                        // If device_category is not "pump," set these columns to null
                        $combinedRow['kw_csv'] = null;
                        $combinedRow['flow_rate_csv'] = null;
                        $combinedRow['pressure_csv'] = null;
                        $combinedRow['flow_pressure'] = null;
                        $combinedRow['pump_efficiency_csv'] = null;
                    }
        
                    // Calculate the efficiency column
                    $output = isset($combinedRow['output']) ? (float) $combinedRow['output'] : null;
        
                    // Avoid division by zero error
                    $efficiency = !is_null($kw_csv) && !is_null($output) && $kw_csv != 0 ? round(($output / $kw_csv) * 100, 2) : null;
        
                    $combinedRow['efficiency'] = $efficiency;
        
                    $combinedData[] = $combinedRow;
                    break; // Break the inner loop after finding a match
                }
            }
        }
        

        foreach ($combinedData as $row) {
            $clientId = $row['Client_Id'];
            $deviceId = $row['Device_Id'];
            $efficiency = $row['efficiency'];
            $dtTime = $row['dt_time']; // Assuming 'dt_time' corresponds to the 'dt_time' column in the 'pumps' table

            // Define the data to be inserted or updated
            $data = [
                'eff' => $efficiency,
                'client_id' => $clientId,
                'device_id' => $deviceId,
                'dt_time' => $dtTime
            ];

            // Define the conditions to identify the row
            $conditions = [
                'client_id' => $clientId,
                'device_id' => $deviceId,
                'dt_time' => $dtTime
            ];

            // Update or insert the row based on conditions
            DB::table('efficiency')->updateOrInsert($conditions, $data);
        }

        return response()->json($combinedData);
    }
}
