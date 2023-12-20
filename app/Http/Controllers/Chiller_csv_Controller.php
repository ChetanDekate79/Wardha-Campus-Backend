<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class Chiller_csv_Controller extends Controller
{
    public function chiller_report($folder, $date)
    {
        // Read data from CSV files
        $csvPaths = [
            "F:/wardha/demowebsite/public/$folder/{$date}_{$folder}.csv",
            
        ];
        $csvData = [];

        foreach ($csvPaths as $csvPath) {
            if (file_exists($csvPath)) {
                $data = array_map('str_getcsv', file($csvPath));
                foreach ($data as $row) {
                    $csvData[] = $row;
                }
            }
        }

        // Prepare an associative array to store the first and last values of the 34th column for each ID and hour
        $hourlyValues = [];

        // Convert the date to the correct format
        $formattedDate = date('Y-m-d', strtotime($date));
      


        foreach ($csvData as $csvRow) {
            $dt_time = strtotime($csvRow[0]);
            $hour = date('H', $dt_time);
            $id = $csvRow[2]; // Assuming the 3rd column contains the ID
            $kwhValue = $csvRow[33]; // Assuming column index 33 contains kWh values
            $flowValue = $csvRow[57]; 
            $column54Value = $csvRow[53]; // 54th column
            $column53Value = $csvRow[52]; // 53rd column

          // Calculate the difference between the 54th and 53rd columns for this row
    $column54Difference = floatval($column53Value) - floatval($column54Value);
    $column54Difference = round($column54Difference, 2);


            if (!isset($hourlyValues[$hour][$id])) {
                $hourlyValues[$hour][$id] = [
                    'hour' => $hour,
                    'id' => $id,
                    'first_kwh' => $kwhValue,
                    'last_kwh' => $kwhValue,
                    'first_flow' => $flowValue,
                    'last_flow' => $flowValue,
                    'kwh_difference' => 0, // Placeholder for kWh difference
                    'flow_difference' => 0, // Placeholder for kWh difference
                    'column54' => [], // Initialize an empty array for column 54 values
                    'column53' => [], // Initialize an empty array for column 53 values
                    'column54_differences' => [], // Initialize an empty array for differences
                ];
            } else {
                $hourlyValues[$hour][$id]['last_kwh'] = $kwhValue;
                $hourlyValues[$hour][$id]['last_flow'] = $flowValue;
                
              
            }
          // Append values to the arrays
            $hourlyValues[$hour][$id]['column54'][] = $column54Value;
            $hourlyValues[$hour][$id]['column53'][] = $column53Value;
            $hourlyValues[$hour][$id]['column54_differences'][] = $column54Difference; // Append the difference to the array
        }

        
        

        // Calculate the difference for each ID and hour
        foreach ($hourlyValues as $hour => $hourData) {
            $totalKwhDifference = 0;
            $totalflowDifference = 0;
           
            foreach ($hourData as $id => $idData) {
                $previousHour = $hour - 1;
                $previousKwh = isset($hourlyValues[$previousHour][$id]) ? $hourlyValues[$previousHour][$id]['last_kwh'] : $idData['first_kwh'];
                $kwhDifference = $idData['last_kwh'] - $previousKwh;
                $hourlyValues[$hour][$id]['kwh_difference'] = round($kwhDifference, 2);
                $totalKwhDifference += $kwhDifference;

                // $kwhDifference = $idData['last_kwh'] - $previousKwh;
                // $hourlyValues[$hour][$id]['kwh_difference'] = round($kwhDifference, 2);
                // $totalKwhDifference += $kwhDifference;
                
                


                $flowDifference = $idData['last_flow'] - $idData['first_flow'];
                $hourlyValues[$hour][$id]['flow_difference'] = round($flowDifference, 2);
                $totalflowDifference += $flowDifference;

            }

            // Add the total kwh_difference for the hour
            $hourlyValues[$hour]['kwh_hourly_total'] = round($totalKwhDifference, 2);

            $hourlyValues[$hour]['flow_hourly_total'] = round($totalflowDifference, 2);
        }

  // Prepare a simplified array to return all rows of the 54th and 53rd columns and their differences for each ID based on the hour:
    $finalOutput = [];
    $maxValuesForHour = [];

    foreach ($hourlyValues as $hour => $hourData) {
        $maxDelta = 0; // Initialize the max_delta for this hour
        $hourlyTotal = $hourData['kwh_hourly_total'];
        $flow = $hourData['flow_hourly_total'];

        $column54DifferencesForHour = []; // Initialize an a

        // Initialize variables to keep track of the maximum ID and maximum delta
        $maxID = null;
        $maxDelta = 0;
    
        foreach ($hourData as $id => $idData) {
            $column54Differences = $idData['column54_differences'];

             // Store the differences for the current ID in the hour
        $column54DifferencesForHour[$id] = $column54Differences;
    
            // Calculate the average for the current ID
            $averageColumn54Differences = !empty($column54Differences) ?
                round(array_sum($column54Differences) / count($column54Differences), 2) : 0;
    
            // Check if the average for the current ID is greater than the current maxDelta
            if ($averageColumn54Differences > $maxDelta) {
                $maxDelta = $averageColumn54Differences;
                $maxID = $id; // Update the ID with the maximum delta
                
            }
            
        }

        // Find the maximum value for each row ("60" and "61")
    $maxValuesForHour[$hour] = [];
    $rowCount = max(count($column54DifferencesForHour["60"]), count($column54DifferencesForHour["61"]));

    for ($i = 0; $i < $rowCount; $i++) {
        $row60Value = isset($column54DifferencesForHour["60"][$i]) ? $column54DifferencesForHour["60"][$i] : 0;
        $row61Value = isset($column54DifferencesForHour["61"][$i]) ? $column54DifferencesForHour["61"][$i] : 0;
        $maxRowValue = max($row60Value, $row61Value);

        $maxValuesForHour[$hour][] = $maxRowValue;
    }

    // Calculate the average of $maxValuesForHour[$hour]
    $averageMaxValuesForHour = !empty($maxValuesForHour[$hour]) ?
        round(array_sum($maxValuesForHour[$hour]) / count($maxValuesForHour[$hour]), 2) : 0;

        $flow_new  = $flow / 2;

        $rt = round($flow_new * $averageMaxValuesForHour * 0.33,0);

        $rt_loss = $rt * 0.6;
    
    
        // Divide kwh_hourly_total by 1000
        $kwh_hourly_total = $hourlyTotal / 1000;
    
        $loss =  round($kwh_hourly_total - $rt_loss,0);
    
        $kwrt = round($kwh_hourly_total / $rt,2);
        
            // Now, create a single entry for this hour with the calculated max_delta
            $finalOutput[] = [
                'hour' => $hour,
                'kwh_hourly_total' => $kwh_hourly_total,
                'flow' => $flow_new,
                'max_delta' => $averageMaxValuesForHour, // Add max_delta for this hour
                'rt' => $rt,
                'kwrt' => $kwrt,
                'loss' => $loss,
                // 'column54_differences' => $column54DifferencesForHour, 
                'average_max_values' => $maxDelta,
              
                // 'max_values_for_row' => $maxValuesForHour[$hour], // 
               
            ];
        }
    

        // Calculate the sum of kwh_hourly_total
    $sumKwh = 0;
    $sumflow = 0;
    $sumrt = 0;
    $sumkwrt = 0;
    $sumloss = 0;
    foreach ($finalOutput as &$entry) {
        $sumKwh += $entry['kwh_hourly_total'];
        $sumflow += $entry['flow'];
        $sumrt += $entry['rt'];
        $sumkwrt = $sumKwh / $sumrt;
        $sumloss = $sumKwh - ($sumrt * 0.6);

        DB::table('chiller_hourly')->updateOrInsert(
            [
                'date' => $formattedDate,
                'hour' => $entry['hour'],
            ],
            [
                'kwh' => $entry['kwh_hourly_total'],
                'flow' => $entry['flow'],
                'delta' => $entry['max_delta'],
                'rt' => $entry['rt'],
                'kwrt' => $entry['kwrt'],
                'loss_kwh' => $entry['loss'],
                // Add other columns and their values here as needed
            ]
        );
        

    }
 
    // Add the sum to the final output
$finalOutput['sum_kwh'] = $sumKwh;
$finalOutput['sum_flow'] = round($sumflow,1);
$finalOutput['sum_rt'] = $sumrt;
$finalOutput['sum_kwrt'] = round($sumkwrt,2);
$finalOutput['sum_loss'] = round($sumloss);



DB::table('chiller')->updateOrInsert(
    ['date' => $formattedDate],
    [
        'kwh' => $finalOutput['sum_kwh'],
        'flow' => $finalOutput['sum_flow'],
        'rt' => $finalOutput['sum_rt'],
        'kwrt' => $finalOutput['sum_kwrt'],
        'loss' => $finalOutput['sum_loss'],
        // Add other columns and their values here as needed
    ]
);

            // Add the maxDelta to all records for this hour
            foreach ($finalOutput as &$record) {
                if ($record['hour'] == $hour) {
                    $record['max_delta'] = $maxDelta;
                    // $record['max_values_for_row'] = $maxValuesForHour[$hour];
                }
            }
        
    if (empty($finalOutput)) {
        return $this->generate_error_html("Data not found for the provided ID.");
    }
    
        return response()->json($finalOutput);
    
        }

       
    public function getChillerData_monthly()
    {
        // Subquery to get multiple dates
        $dynamicDateResults = DB::select("
            SELECT MAX(DATE(dt_time)) AS date
            FROM jnmc_all_kwh
            GROUP BY MONTH(dt_time)
        ");

        // Array to store results for each date
        $allResults = [];

        // Loop through each date from the subquery
        foreach ($dynamicDateResults as $dynamicDateResult) {
            $dynamicDate = $dynamicDateResult->date;

            // Main query with dynamic date
            $query = "
            SELECT Z.client_Name, Z.date,sum(Z.monthly_kwh) FROM (SELECT 
  q1.Client_Name, 
  DATE(q1.dt_time) AS date,
 sum(ROUND(q2.min_wh_R - COALESCE(q3.min_wh_R_month, 0), 0) / 1000 
        )  AS monthly_kwh
          
FROM (
  SELECT 
    T1.dt_time,
    T2.client_name AS Client_Name,
    T2.device_id AS Device_Id,
    T2.client_id AS Client_Id,
    T2.device_name AS Device_Name,
    T1.max_wh_R,
    T1.min_wh_D,
    T1.min_wh_R,
    T1.max_wh_D
  FROM (
    SELECT 
      MAX(wh_R) AS max_wh_R,
      MIN(wh_R) AS min_wh_R,
      MIN(wh_D) AS min_wh_D,
      max(wh_D) AS max_wh_D,
      dt_time,
      host,
      device_id
    FROM jnmc_all_kwh
    WHERE DATE(dt_time) = :dynamicDate AND client_id = 'chiller-avbrh'
    GROUP BY host, device_id,dt_time
  ) T1
  INNER JOIN device_details_wardha T2 ON T1.host = T2.client_id AND T1.device_id = T2.device_id
) q1
LEFT JOIN (
  SELECT 
    MAX(wh_R) AS max_wh_R,
    MIN(wh_R) AS min_wh_R,
    MIN(wh_D) AS min_wh_D,
    MAX(wh_D) AS max_wh_D,
    device_id,
    host
  FROM jnmc_all_kwh
  WHERE DATE(dt_time) = DATE(:dynamicDate) + INTERVAL 1 DAY AND client_id = 'chiller-avbrh'
  GROUP BY device_id, HOST 
) q2 ON q1.Device_Id = q2.device_id AND q1.Client_Id = q2.host
LEFT JOIN (
  SELECT 
    MAX(wh_R) AS max_wh_R_month,
    min(wh_R) AS min_wh_R_month,
    MIN(wh_D) AS min_wh_D_month,
    MAX(wh_D) AS max_wh_D_month,
    device_id,
    host
  FROM jnmc_all_kwh
  WHERE 
    YEAR(dt_time) = YEAR(DATE(:dynamicDate)) AND
    MONTH(dt_time) = MONTH(DATE(:dynamicDate)) AND client_id = 'chiller-avbrh'
  GROUP BY device_id, host
) q3 ON q1.Device_Id = q3.device_id AND q1.Client_Id = q3.host
GROUP BY q1.Client_Name, DATE(q1.dt_time), q1.device_id,q1.Device_Name
ORDER BY q1.Client_Name, Device_Name) Z GROUP BY Z.Client_Name, Z.date
ORDER BY Z.Client_Name, Z.date;
            ";

            // Execute the main query with the dynamic date
            $results = DB::select($query, ['dynamicDate' => $dynamicDate]);

            // Store results for each date
            $allResults[$dynamicDate] = $results;
        }

        return response()->json($allResults);
    }
    }
    