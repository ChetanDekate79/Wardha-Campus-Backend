<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use App\Models\pump_report; 
use Illuminate\Http\Request;

class Pumpcsv_Controller extends Controller
{
    public function pump_report($folder, $date, $id)
    {
        // Read data from CSV files
        $csvPaths = [
            "F:/wardha/demowebsite/public/{$folder}/{$date}_{$folder}.csv",
            // "C:/Inetpub/vhosts/hetadatain.com/wardha.hetadatain.com/JNMC_2_copy/$folder/{$date}_{$folder}.csv",
            // "C:/Inetpub/vhosts/hetadatain.com/wardha.hetadatain.com/JNMC_3_copy/$folder/{$date}_{$folder}.csv"
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

        // SQL query to fetch data from the database
        $query = "
            SELECT DISTINCT hk.id, hk.client_id, hk.device_id, hk.client_name, hk.device_name
            FROM device_details_wardha AS hk
            WHERE hk.client_id = ?
            AND hk.device_id = ?
            ORDER BY hk.client_name, hk.device_name
        ";

    // Execute the query and get the results
    $results = DB::select($query, [$folder, $id]);

    // Prepare an associative array to group data by hour
    $groupedData = [];

    foreach ($csvData as $csvRow) {
        foreach ($results as $result) {
            if ($csvRow[1] === $result->client_id && $csvRow[2] == $result->device_id) {
                $dt_time = strtotime($csvRow[0]);
                $hour = date('H', $dt_time);
                $sum_kw = ($csvRow[4] > 0) ? $csvRow[4] : 0;

                if (!isset($groupedData[$hour])) {
                    $groupedData[$hour] = [
                        'hour' => $hour,
                        'rows_added' => ($csvRow[4] > 0) ? 1 : 0,
                        'sum_kw' => $sum_kw,
                        'time' => ($csvRow[4] > 0) ? 2 : 0,
                        'no_records' => 1,
                        'flow' => 0, // Placeholder for flow column
                        'flow_rate' => 0, // Placeholder for flow column
                        'flow_pressure' => 0, // Placeholder for flow column
                        'efficiency' => 0,
                        'pressure_sum' => $csvRow[51], // Initializing pressure sum
                        'pressure_count' => 1, // Initializing pressure count
                        'lavel' => $csvRow[52], // Initializing the last column value
                        'sql_result' => $result,
                    ];
                } else {
                    $groupedData[$hour]['rows_added'] += ($csvRow[4] > 0) ? 1 : 0;
                    $groupedData[$hour]['sum_kw'] += $sum_kw;
                    $groupedData[$hour]['time'] += ($csvRow[4] > 0) ? 2 : 0;
                    $groupedData[$hour]['no_records']++;
                    $groupedData[$hour]['kwh_data'][] = $csvRow[33]; // Assuming column index 33 contains kWh values

                    // Sum the pressure values for pressure calculation
                    // $groupedData[$hour]['pressure_sum'] += $csvRow[51];
                    // $groupedData[$hour]['pressure_count']++;

                    // Sum the pressure values for pressure calculation
                    if ($csvRow[51] > 0) {
                        $groupedData[$hour]['pressure_sum'] += $csvRow[51];
                        $groupedData[$hour]['pressure_count']++;
                    }


                    // Store the 3rd last column value for flow calculation
                    $groupedData[$hour]['flow_data'][] = $csvRow[count($csvRow) - 3];

                    // Update the last column value
                    $groupedData[$hour]['lavel'] = $csvRow[52];
                }

                break; // Match found, no need to check further SQL rows
            }
        }
    }

    // Calculate flow by subtracting the last row from the first row for each hour
    foreach ($groupedData as &$hourData) {
        if (isset($hourData['flow_data']) && count($hourData['flow_data']) >= 2) {
            $firstFlowValue = reset($hourData['flow_data']);
            $lastFlowValue = end($hourData['flow_data']);
            $hourData['flow'] = round($lastFlowValue - $firstFlowValue, 2); // Round to 2 decimal places
            $hourData['flow_rate'] = round($hourData['flow'] * 0.277,2);

            $hourData['flow_2'] = round($hourData['flow'] / 3600,2);
        }
        unset($hourData['flow_data']);

        // Calculate average pressure for each hour
        if ($hourData['pressure_count'] > 0) {
            $hourData['pressure'] = $hourData['pressure_sum'] / $hourData['pressure_count'];
            $hourData['pressure'] = number_format($hourData['pressure'], 2); // Format to 2 decimal places
        } else {
            $hourData['pressure'] = 0;
        }
        unset($hourData['pressure_sum']);
        unset($hourData['pressure_count']);

        // Calculate kWh difference for each hour
        if (isset($hourData['kwh_data']) && count($hourData['kwh_data']) >= 2) {
            $firstKwhValue = reset($hourData['kwh_data']);
            $lastKwhValue = end($hourData['kwh_data']);
            $kwhDifference = round($lastKwhValue - $firstKwhValue, 2); // Round to 2 decimal places
            $hourData['kwh_difference'] = $kwhDifference /1000 ;
        }
        unset($hourData['kwh_data']);

        // Round the sum_kw column to 2 decimal places
        $hourData['sum_kw'] = round($hourData['sum_kw'], 2);

        // Round the lavel column to 2 decimal places
        $hourData['lavel'] = round($hourData['lavel'], 2);

        $hourData['flow_pressure'] = round($hourData['flow_rate'] * ($hourData['pressure'] ),2 );



        // if ($hourData['kwh_difference'] == 0 && $hourData['flow_pressure'] == 0) {
        //     $hourData['efficiency'] = 0; // Set efficiency to 0 if either value is 0
        // } else {
        //     $hourData['efficiency'] = round($hourData['flow_pressure'] / $hourData['kwh_difference'] *10, 2);
        // }

        if ($hourData['kwh_difference'] == 0 || $hourData['flow_pressure'] == 0) {
            $hourData['efficiency'] = 0; // Set efficiency to 0 if either value is 0
        } else {
            $hourData['efficiency'] = round($hourData['flow_pressure'] / $hourData['kwh_difference'] * 10, 2);
        }

       
            $hourData['fp'] = round($hourData['flow'] / 3600 *10 * $hourData['pressure'] * 10 , 2);

            if ($hourData['kwh_difference'] == 0) {
                $hourData['efficiency_2'] = 0; // Set efficiency to 0 if either value is 0
            } else {
                $hourData['efficiency_2'] = round($hourData['fp'] / $hourData['kwh_difference'] * 100 , 2);
            }

        
    
// var_dump($hourData['flow_pressure']);


}

    // Convert the associative array to a simple array
    $finalOutput = array_values($groupedData);
        

        if (empty($finalOutput)) {
            return $this->generate_error_html("Data not found.");
        }
    
        return $this->generate_html($folder, $date, $id, $finalOutput);
    
       

 
        // Return the final output as a JSON response
        // return response()->json($finalOutput);
        // return $this->generate_html($folder, $date, $id,$finalOutput);
    }
    public function generate_html($folder, $date, $id, $finalOutput) {
        $pump_report = new pump_report();
        $tableRows = '';
        foreach ($finalOutput as $value) {
            $tableRows .= '<tr>
                <td>' . $value['hour'] . '</td>
                <td>' . $value['sql_result']->device_id . '</td>
                <td>' . $value['kwh_difference'] . '</td>
                <td>' . $value['time'] . '</td>
                <td>' . $value['flow'] . '</td>
                <td>' . $value['flow_rate'] . '</td>
                <td>' . $value['pressure'] . '</td>
                <td>' . $value['flow_pressure'] . '</td>
                
                <td>' . $value['lavel'] . '</td>
                <td>' . $value['efficiency'] . '</td>
                <td>' . $value['efficiency_2'] . '</td>
                </tr>';
        }
    
        $tableContent = '
            <thead>
                <tr>
                <th>Hour</th>
                <th>Device id</th>
                <th>KWH</th>
                <th>Running Time</th>
                <th>Flow cumm.(m3/h)</th>
                <th>Flow Rate</th>
                <th>Avg. Pressure</th>
                <th>Flow Pressure</th>
                <th>Level</th>
                <th>Efficiency</th>
                <th>Efficiency 2</th>
                </tr>
            </thead>
            <tbody>' . $tableRows . '</tbody>';
    
        $htmlContent = '
            <html>
            <head>
                <title>JNMC-' . $date . '</title>
                <style>
                    body {
                        font-family: "Comic Sans MS", cursive, sans-serif;
                    }
                    table {
                        font-family: Comic Sans MS;
                        border-collapse: collapse;
                        width: 100%;
                    }
                    th, td {
                        border: 1px solid #dddddd;
                        text-align: left;
                        padding: 8px;
                    }
                    .std{ border: 0px; !important}
                    thead{ background-color: #dddddd;}
                    .flex-container {
                        display: flex;
                      }
                    .header-report {
                        top: -60px;
                        left: -60px;
                        right: -60px;
                        background-color: #d1fec5;
                        color: white;
                        text-align: center;
                        line-height: 35px;
                    }
                    @page { margin: 50px 25px 25px 25px; }
                    footer { position: fixed; bottom: -60px; left: 0px; right: 0px; }
                    .footer .page-number:after { content: counter(page); }
                    /* Your other CSS styles here */
                </style>
            </head>
            <body>
                <div class="header-report">
                    <table>
                        <tr>
                            <td class="std" style="text-align: left;">
                                <span style="">
                                    <img width="150px" src="'.$pump_report->heta_logo.'" id="">
                                </span>
                            </td>
                            <td class="std txt-align" style="text-align: right;">
                                <span style="">
                                    <img width="100px" src="'.$pump_report->plasto_logo.'" id="">
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
                <center>
                    <div>
                    <h2>' . $value['sql_result']->client_name . ' - ' . $value['sql_result']->device_name . ' Report</h2>

                        <h3> Hourly Energy Report For Date - ' . $date . ' </h3>
                    </div>
                </center>
                <hr>
                <h3>Energy Consumption</h3>
                <table>' . $tableContent . '</table>
                <footer class="footer">
                    <span class="page-number">[Page: ] </span>
                    <span style="text-align:center padding:0 20%;">
                        Heta Datain www.hetadatain.com
                    </span>
                </footer>
            </body>
            </html>';
    
        return $htmlContent;
    }
    public function generate_error_html($errorMessage)
{
    $htmlContent = '
        <html>
        <head>
            <title>Error</title>
            <style>
                body {
                    font-family: "Comic Sans MS", cursive, sans-serif;
                    text-align: center;
                }
                .error-message {
                    color: red;
                    font-size: 24px;
                    margin-top: 100px;
                }
            </style>
        </head>
        <body>
            <div class="error-message">
                <p>' . $errorMessage . '</p>
            </div>
        </body>
        </html>';

    return $htmlContent;
}
    
}
