<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // Import the DB facade

class MeterStatusController extends Controller
{
    public function processCsv($folder, $date)
    {
        // Read data from CSV files
        $csvPaths = [
            "F:/wardha/demowebsite/public/{$folder}/{$date}_{$folder}.csv",
            // "C:/Inetpub/vhosts/hetadatain.com/wardha.hetadatain.com/JNMC_2_copy/$folder/{$date}_{$folder}.csv",
            // "C:/Inetpub/vhosts/hetadatain.com/wardha.hetadatain.com/JNMC_3_copy/$folder/{$date}_{$folder}.csv"
        ];

        $csvData = [];
        $uniqueSecondThirdColumns = []; // To store unique combinations of the 2nd and 3rd columns

        foreach ($csvPaths as $csvPath) {
            if (file_exists($csvPath)) {
                $data = array_map('str_getcsv', file($csvPath));
                foreach ($data as $row) {
                    $secondColumn = $row[1];
                    $thirdColumn = $row[2];
                    $timestamp = strtotime($row[0]);

                    $columnCombination = $secondColumn . '-' . $thirdColumn;

                    if (!in_array($columnCombination, $uniqueSecondThirdColumns)) {
                        // If the combination is unique, add the row to the $csvData array
                        $uniqueSecondThirdColumns[] = $columnCombination;
                        $csvData[$columnCombination] = [$row[0], $row[1], $row[2],$row[3], $row[4], $row[5], $row[6], $row[7]]; // Select only the first three columns
                    } else {
                        // If the combination is not unique, check the timestamp to select the row with the largest value
                        $existingTimestamp = strtotime($csvData[$columnCombination][0]);
                        if ($timestamp > $existingTimestamp) {
                            $csvData[$columnCombination] = [$row[0], $row[1], $row[2],$row[3], $row[4], $row[5], $row[6], $row[7]]; // Select only the first three columns
                        }
                    }
                }
            }
        }

        // Get the values from the $csvData array
        $csvData = array_values($csvData);

        // SQL query to fetch data from the database
        $query = "
            SELECT DISTINCT hk.id, hk.client_id, hk.device_id, hk.client_name, hk.device_name
            FROM device_details_wardha AS hk
            WHERE hk.client_id = ?
            ORDER BY hk.client_name, hk.device_name
        ";

        // Execute the query and get the results
        $results = DB::select($query, [$folder]);

        // Convert "00:10:00" (10 minutes) to seconds
        $tenMinutesInSeconds = 10 * 60;

        // Set the timezone to "Asia/Kolkata" (you can change it to your specific timezone)
        date_default_timezone_set('Asia/Kolkata');


        // Merge CSV data and SQL query results into a single array based on the condition
        $mergedData = [];
        foreach ($csvData as $csvRow) {
            $csvSecondColumn = $csvRow[1];
            $csvThirdColumn = $csvRow[2];
            $columnCombination = $csvSecondColumn . '-' . $csvThirdColumn;

            foreach ($results as $result) {
                $sqlSecondColumn = $result->client_id;
                $sqlThirdColumn = $result->device_id;

                if ($csvSecondColumn === $sqlSecondColumn && $csvThirdColumn == $sqlThirdColumn) {
                    // Extract client_name and device_name from the $results array
                    $clientName = $result->client_name;
                    $deviceName = $result->device_name;

                    // Get the current date and time as it is
                    $currentDateTime = date('d-m-Y H:i:s');

                    $mergedRow = array_merge($csvRow, [$clientName, $deviceName, $currentDateTime]);

                    // Calculate the difference between the first and last columns and add it as a new column
                    $firstColumnTimestamp = strtotime($csvRow[0]);
                    $lastColumnTimestamp = strtotime($currentDateTime);
                    $differenceInSeconds = abs($lastColumnTimestamp - $firstColumnTimestamp);

                    // Convert the difference to HH:MM:SS format
                    $differenceHHMMSS = sprintf('%02d:%02d:%02d', ($differenceInSeconds / 3600), ($differenceInSeconds / 60 % 60), ($differenceInSeconds % 60));
                    $mergedRow[] = $differenceHHMMSS;

                    // Add a new column to compare the difference with 10 minutes (00:10:00) in seconds
                    $tenMinutesInSeconds = 10 * 60;
                    $isDifferenceLessThanOrEqualTo10Minutes = ($differenceInSeconds <= $tenMinutesInSeconds) ? 1 : 0;
                      // Check if the values in columns 6, 7, and 8 are greater than 0
                      $column6 = $csvRow[5];
                      $column7 = $csvRow[6];
                      $column8 = $csvRow[7];
                      $isColumn678GreaterThan0 = ($column6 > 0 && $column7 > 0 && $column8 > 0) ? 1 : 0;

            // Add a new column to compare the difference with 10 minutes (00:10:00) in seconds
            $tenMinutesInSeconds = 10 * 60;
            $isDifferenceLessThanOrEqualTo10Minutes = ($differenceInSeconds <= $tenMinutesInSeconds) ? 1 : 0;

            // Add one more condition: if columns 6, 7, or 8 are less than 0 and time difference is less than or equal to 10, set the last column to 3
            if (($column6 < 0 || $column7 < 0 || $column8 < 0) && $isDifferenceLessThanOrEqualTo10Minutes) {
                $mergedRow[] = 3;
            } else if (($column6 == 0 || $column7 == 0 || $column8 == 0) && $isDifferenceLessThanOrEqualTo10Minutes) {
                $mergedRow[] = 2;
            } else {
                // Set the last column value based on other conditions
                $lastColumnValue = ($isDifferenceLessThanOrEqualTo10Minutes && $isColumn678GreaterThan0) ? 1 : 0;
                $mergedRow[] = $lastColumnValue;
            }

            $mergedData[] = $mergedRow;
            break;  // We found a match, no need to check further SQL rows
                }
            }
        }
        // Return the merged data as a JSON response
        return response()->json($mergedData);
    }
}