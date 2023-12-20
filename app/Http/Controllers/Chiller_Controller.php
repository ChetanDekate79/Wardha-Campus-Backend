<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class Chiller_Controller extends Controller
{
    public function getByFolderDateId($folder, $date)
    {
        $csvPaths = [
            "F:/wardha/demowebsite/public/$folder/{$date}_{$folder}.csv",
        ];
        $groupedData = [];
        $kwrtValues = [];

        foreach ($csvPaths as $csvPath) {
            if (file_exists($csvPath)) {
                $data = array_map('str_getcsv', file($csvPath));

                for ($i = 0; $i < count($data); $i += 2) {
                    if (isset($data[$i + 1])) {
                       // Group every two rows together
                for ($i = 0; $i < count($data); $i += 2) {
                    if (isset($data[$i + 1])) {
                        $firstColumn = $data[$i][0];  // Extract the first column value from the first row
                        $secondColumn = $data[$i][1];  // Extract the second column value from the first row

                        $firstinput = (float) $data[$i][52];   // Extract the 53rd value (index 52) from the first row
                        $secondinput = (float) $data[$i + 1][52];  // Extract the 53rd value (index 52) from the second row
                       

                        $firstoutput = (float) $data[$i][53];   // Extract the 53rd value (index 52) from the first row
                        $secondoutput = (float) $data[$i + 1][53];  // Extract the 53rd value (index 52) from the second row
                       

                        $condenserfirstinput = (float) $data[$i][54];   // Extract the 53rd value (index 52) from the first row
                        $condensersecondinput = (float) $data[$i + 1][54];  // Extract the 53rd value (index 52) from the second row
                       

                        $condenserfirstoutput = (float) $data[$i][55];   // Extract the 53rd value (index 52) from the first row
                        $condensersecondoutput = (float) $data[$i + 1][55];  // Extract the 53rd value (index 52) from the second row
                       

                        $firstkw = (float) $data[$i][4] / 1000;   // Extract the 53rd value (index 52) from the first row
                        $secondkw = (float) $data[$i + 1][4] / 1000;  // Extract the 53rd value (index 52) from the second row
                        $sumOfkw = $firstkw + $secondkw;

                        $flow_rate = (float) $data[$i][56];
                        $flow = (float) $data[$i][57];

                        $sumOfinput = $firstinput - $firstoutput;
                        $sumOfoutput = $secondinput - $secondoutput;
                        $condensersumOfinput =  $condenserfirstoutput - $condenserfirstinput;
                        $condensersumOfoutput = $condensersecondoutput - $condensersecondinput;

                        $maxofsum = max($sumOfinput,$sumOfoutput);

                        $rt = $flow_rate * $maxofsum * 0.33;

                        $kwrt = 0; // Initialize to a default value

                        // Check if $rt is not zero before performing the division
                        if ($rt != 0) {
                            $kwrt = $sumOfkw / $rt;
                        }

                        // Calculate kwrt and add to the kwrtValues array
                        $kwrtValues[] = $kwrt;

                        // Keep only the last 5 kwrt values
                        if (count($kwrtValues) > 5) {
                            array_shift($kwrtValues);
                        }

                        // Calculate the average of kwrt values
                        $averageKwrt = count($kwrtValues) > 0 ? array_sum($kwrtValues) / count($kwrtValues) : 0;

                        $groupedData[] = [
                            $firstColumn,
                            $secondColumn,
                            $firstinput,
                            $firstoutput,
                            $sumOfinput,
                            $secondinput,
                            $secondoutput,
                            $sumOfoutput,
                            $condenserfirstinput,
                            $condenserfirstoutput,
                            $condensersumOfinput,
                            $condensersecondinput,
                            $condensersecondoutput,
                            $condensersumOfoutput,
                            $firstkw,
                            $secondkw,
                            $sumOfkw,
                            $flow_rate,
                            $flow,
                            $rt,
                            $kwrt,
                            $averageKwrt, // Add averageKwrt to the grouped data
                        ];
                    }
                }
            }
        }

        return response()->json(array_values($groupedData));
    }
}
    }}