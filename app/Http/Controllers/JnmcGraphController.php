<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JnmcGraphController extends Controller
{
    public function getGraphData(Request $request)
    {
        $host = $request->query('host');
        $device_id = $request->query('device_id');
        $date = $request->query('date');

        $results = DB::select("
            WITH cte AS (
                SELECT
                    CONCAT('hour ', HOUR(dt_time) - 1, ' - hour ', HOUR(dt_time)) AS time_range,
                    device_id,
                    HOST,
                    ROUND((ROUND(wh_R, 0) - ROUND(LAG(wh_R) OVER (ORDER BY dt_time), 0)) / 1000) AS value,
                    ROUND((ROUND(wh_D, 0) - ROUND(LAG(wh_D) OVER (ORDER BY dt_time), 0)) / 1000) AS value1,
                    ROUND((ROUND(wh_1, 0) - ROUND(LAG(wh_1) OVER (ORDER BY dt_time), 0)) / 1000) AS value2,
                    ROUND((ROUND(wh_2, 0) - ROUND(LAG(wh_2) OVER (ORDER BY dt_time), 0)) / 1000) AS value3,
                    ROUND((ROUND(wh_3, 0) - ROUND(LAG(wh_3) OVER (ORDER BY dt_time), 0)) / 1000) AS value4,
                    dt_time,
                    ROW_NUMBER() OVER (ORDER BY dt_time) AS row_number,
                    ROUND(ROUND(FIRST_VALUE(wh_R) OVER (ORDER BY dt_time), 0) / 1000, 0) AS first_wh_R,
                    ROUND(ROUND(LAST_VALUE(wh_R) OVER (ORDER BY dt_time), 0) / 1000, 0) AS last_wh_R
                FROM jnmc_all_kwh
                WHERE (CAST(dt_time AS DATE) BETWEEN DATE_SUB(?, INTERVAL 1 DAY) AND ?)
                    AND device_id = ? AND HOST = ?
                GROUP BY dt_time, device_id, HOST, wh_R, wh_D, wh_1, wh_2, wh_3
            )
            SELECT
                time_range,
                device_id,
                HOST,
                value,
                value1,
                value2,
                value3,
                value4,
                CASE WHEN row_number = 1 THEN (SELECT dt_time FROM cte WHERE row_number = 2) ELSE dt_time END AS dt_time,
                first_wh_R AS first_wh_R,
                last_wh_R AS last_wh_R
            FROM (
                SELECT *
                FROM cte
                WHERE CAST(dt_time AS DATE) = DATE_SUB(?, INTERVAL 1 DAY)
                    AND row_number = (SELECT MAX(row_number) FROM cte WHERE CAST(dt_time AS DATE) = DATE_SUB(?, INTERVAL 1 DAY))
                UNION ALL
                SELECT *
                FROM cte
                WHERE CAST(dt_time AS DATE) = ?
            ) t
            ORDER BY dt_time;
        ", [$date, $date, $device_id, $host, $date, $date, $date]);


        $filteredResults = [];
        
        foreach ($results as $key => $result) {
            // Exclude the second row from the results
            if ($key !== 1) {
                if ($key === 0) {
                    // Copy dt_time of the second row into the first dt_time
                    $result->dt_time = $results[1]->dt_time;
                }
                $filteredResults[] = $result;
            }
        }
        
        // Add an additional filter to skip the last row if the last two rows have the same time_range
        $count = count($filteredResults);
        if ($count >= 2 && $filteredResults[$count - 1]->time_range === $filteredResults[$count - 2]->time_range) {
            array_pop($filteredResults);
        }
        
        return response()->json($filteredResults);
        
    }
    public function getGraphDatanew(Request $request)
{
    $host = $request->query('host');
    $device_id = $request->query('device_id');
    $date = $request->query('date');

    $results = DB::select("
        SELECT 
            dt_time,
            HOUR,
            HOST,
            device_id,
            wh_R,
            wh_D,
            wh_1,
            wh_2,
            wh_3,
            @prev_wh_R AS value,
            @prev_wh_D AS value1,
            @prev_wh_1 AS value2,
            @prev_wh_2 AS value3,
            @prev_wh_3 AS value4,
            (@prev_wh_R - wh_R) AS sum_wh_R_difference,
            (@prev_wh_D - wh_D) AS sum_wh_D_difference,
            (@prev_wh_1 - wh_1) AS sum_wh_1_difference,
            (@prev_wh_2 - wh_2) AS sum_wh_2_difference,
            (@prev_wh_3 - wh_3) AS sum_wh_3_difference,
            @prev_wh_R := wh_R AS dummy1,
            @prev_wh_D := wh_D AS dummy2,
            @prev_wh_1 := wh_1 AS dummy3,
            @prev_wh_2 := wh_2 AS dummy4,
            @prev_wh_3 := wh_3 AS dummy5
        FROM (
            SELECT 
                dt_time,
                HOUR,
                HOST,
                device_id,
                wh_R,
                wh_D,
                wh_1,
                wh_2,
                wh_3
            FROM jnmc_all_kwh 
            WHERE DATE(dt_time) = ? AND HOST = ? AND device_id = ?
            ORDER BY dt_time DESC
        ) AS reversed_subquery, 
        (SELECT 
            @prev_wh_R := NULL, 
            @prev_wh_D := NULL, 
            @prev_wh_1 := NULL, 
            @prev_wh_2 := NULL, 
            @prev_wh_3 := NULL) AS vars
        WHERE dt_time <> (SELECT MAX(dt_time) FROM jnmc_all_kwh WHERE DATE(dt_time) = ? AND HOST = ? AND device_id = ?)
        ORDER BY dt_time;
    ", [$date, $host, $device_id, $date, $host, $device_id]);

    return response()->json($results);
}



}
