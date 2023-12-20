<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;

use Illuminate\Http\Request;

class Total_consumptionController extends Controller
{
  public function getKwhData($date, $host, $device)
  {
      $query = "SELECT
      HOUR(q2.dt_time) AS HOUR,
      q2.dt_time,
      q2.HOST,
      q2.device_id,
      q2.wh_R - q1.wh_R AS value,
      q2.wh_D - q1.wh_D  AS value1,
      q2.wh_1 - q1.wh_1  AS value2,
      q2.wh_2 - q1.wh_2  AS value3,
      q2.wh_3 - q1.wh_3  AS value4
  FROM
      (SELECT dt_time, HOUR, HOST, device_id, FLOOR(wh_R/1000 ) AS wh_R,  
                FLOOR(wh_D / 1000) AS wh_D, 
               FLOOR(wh_1 / 1000) AS wh_1,
                FLOOR(wh_2 / 1000) AS wh_2,
             FLOOR(wh_3 / 1000) AS wh_3
       FROM jnmc_all_kwh
       WHERE DATE(dt_time) = ?
         AND HOUR = 23
         AND HOST = ?
         AND device_id =?) q1
  
  LEFT JOIN
  
      (SELECT dt_time, HOUR, HOST, device_id, FLOOR(wh_R/1000 ) AS wh_R,  
                FLOOR(wh_D / 1000) AS wh_D, 
               FLOOR(wh_1 / 1000) AS wh_1,
                FLOOR(wh_2 / 1000) AS wh_2,
             FLOOR(wh_3 / 1000) AS wh_3
       FROM jnmc_all_kwh
       WHERE DATE(dt_time) = DATE( ? + INTERVAL 1 day)
         AND HOUR = 0
         AND HOST = ?
         AND device_id =?) q2
  
  ON q1.device_id = q2.device_id
  
  UNION
  
  SELECT HOUR(next_dt_time) as HOUR, next_dt_time as dt_time, HOST, device_id, value, value1, value2, value3, value4
  FROM (
    SELECT dt_time, HOUR, HOST, device_id, wh_R, wh_D, wh_1, wh_2, wh_3, @prev_dt_time AS next_dt_time,
           IF(@prev_dt_time IS NULL, NULL, @prev_wh_R - wh_R) AS value, 
           IF(@prev_dt_time IS NULL, NULL, @prev_wh_D - wh_D) AS value1,
           IF(@prev_dt_time IS NULL, NULL, @prev_wh_1 - wh_1) AS value2, 
           IF(@prev_dt_time IS NULL, NULL, @prev_wh_2 - wh_2) AS value3,
           IF(@prev_dt_time IS NULL, NULL, @prev_wh_3 - wh_3) AS value4, 
           @prev_dt_time := dt_time AS dummy, 
           @prev_wh_R := wh_R AS dummy1,
           @prev_wh_D := wh_D AS dummy2, 
           @prev_wh_1 := wh_1 AS dummy3, 
           @prev_wh_2 := wh_2 AS dummy4, 
           @prev_wh_3 := wh_3 AS dummy5
    FROM (
      SELECT dt_time, HOUR, HOST, device_id, FLOOR(wh_R/1000 ) AS wh_R,  
                FLOOR(wh_D / 1000) AS wh_D, 
               FLOOR(wh_1 / 1000) AS wh_1,
                FLOOR(wh_2 / 1000) AS wh_2,
             FLOOR(wh_3 / 1000) AS wh_3
      FROM jnmc_all_kwh 
      WHERE DATE(dt_time) = ? AND HOST = ? AND device_id =? 
      ORDER BY dt_time DESC
    ) AS reversed_subquery, 
    (SELECT @prev_dt_time := NULL, @prev_wh_R := NULL, @prev_wh_D := NULL, @prev_wh_1 := NULL, @prev_wh_2 := NULL, @prev_wh_3 := NULL) AS vars
  ) AS calculated_data
  WHERE dt_time <> (
    SELECT MAX(dt_time) 
    FROM jnmc_all_kwh 
    WHERE DATE(dt_time) = ? AND HOST = ? AND device_id =?
  ) ORDER BY dt_time;";

      $result = DB::select($query, [$date, $host, $device, $date, $host, $device, $date, $host, $device, $date, $host, $device]);

      $filteredResult = [];
      $prevHour = null;
      $lastRow = null;
  
      foreach ($result as $index => $row) {
          $currentHour = $row->HOUR;
          $isLastRow = ($index === count($result) - 1); // Check if it's the last row
  
          if ($prevHour === null || abs($currentHour - $prevHour) <= 1 || ($prevHour === 0 && !$isLastRow)) {
              $filteredResult[] = $row;
          }
  
          $prevHour = $currentHour;
          $lastRow = $row;
      }
  
      // Check if the last row has an hour value of 0
      if ($lastRow !== null && $lastRow->HOUR === 0) {
          $filteredResult[] = $lastRow;
      }
      
          return response()->json($filteredResult);
      }
}
