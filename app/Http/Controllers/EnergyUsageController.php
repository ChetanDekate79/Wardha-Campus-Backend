<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EnergyUsageController extends Controller
{
    public function getEnergyUsage(Request $request)
    {
        $date = $request->query('date');

        $kwhData = DB::select("
        SELECT 
        q1.dt_time,
        q1.Client_Name,
        q1.Client_Id,
        q1.Device_Name,
        q1.Device_Id,
        CONCAT(ROUND(q1.max_wh_R - COALESCE(q2.max_wh_R, 0), 0) / 1000, ' /(', ROUND(COALESCE(q2.min_wh_D, 0) - q1.min_wh_D, 0) / 1000, ')') AS today_kwh,
        CONCAT(ROUND(COALESCE(q2.max_wh_R, 0) - q4.max_wh_R_prev_day, 0) / 1000, ' /(', ROUND(COALESCE(q4.min_wh_D_prev_day, 0) - COALESCE(q2.min_wh_D, 0), 0) / 1000, ')') AS yesterday_kwh,
        CONCAT(ROUND(q1.max_wh_R - COALESCE(q3.max_wh_R_prev_month, 0), 0) / 1000, ' /(', ROUND(COALESCE(q3.min_wh_D_prev_month, 0) - q1.min_wh_D, 0) / 1000, ')') AS monthly_kwh,
        CONCAT(ROUND(q1.max_wh_R - COALESCE(q5.max_wh_R_prev_year, 0), 0) / 1000, ' /(', ROUND(COALESCE(q5.min_wh_D_prev_year, 0) - q1.min_wh_D, 0) / 1000, ')') AS yearly_kwh
      FROM (
        SELECT 
          T1.dt_time,
          T2.client_name AS Client_Name,
          T2.device_id AS Device_Id,
          T2.client_id AS Client_Id,
          T2.device_name AS Device_Name,
          T1.max_wh_R,
          T1.min_wh_D
        FROM (
          SELECT 
            MAX(wh_R) AS max_wh_R,
            MIN(wh_D) AS min_wh_D,
            dt_time,
            host,
            device_id
          FROM jnmc_all_kwh
          WHERE DATE(dt_time) = ?
          GROUP BY dt_time, host, device_id
        ) T1
        INNER JOIN device_details_wardha T2 ON T1.host = T2.client_id AND T1.device_id = T2.device_id
      ) q1
      LEFT JOIN (
        SELECT 
          q2.device_id,
          q2.host,
          q2.max_wh_R,
          q2.min_wh_D
        FROM (
          SELECT 
            device_id,
            host,
            MAX(wh_R) AS max_wh_R,
            MIN(wh_D) AS min_wh_D
          FROM jnmc_all_kwh
          WHERE DATE(dt_time) = DATE(?) - INTERVAL 1 DAY
          GROUP BY device_id, host
        ) q2
      ) q2 ON q1.Device_Id = q2.device_id AND q1.Client_Id = q2.host
      LEFT JOIN (
        SELECT 
          device_id,
          host,
          MAX(wh_R) AS max_wh_R_prev_month,
          MIN(wh_D) AS min_wh_D_prev_month
        FROM jnmc_all_kwh
        WHERE YEAR(dt_time) = YEAR(DATE(?)) AND MONTH(dt_time) = MONTH(DATE(?)) - 1
        GROUP BY device_id, host
      ) q3 ON q1.Device_Id = q3.device_id AND q1.Client_Id = q3.host
      LEFT JOIN (
        SELECT 
          device_id,
          host,
          MAX(wh_R) AS max_wh_R_prev_day,
          MIN(wh_D) AS min_wh_D_prev_day
        FROM jnmc_all_kwh
        WHERE DATE(dt_time) = DATE(?) - INTERVAL 2 DAY
        GROUP BY device_id, host
      ) q4 ON q1.Device_Id = q4.device_id AND q1.Client_Id = q4.host
      LEFT JOIN (
        SELECT 
          device_id,
          host,
          MAX(wh_R) AS max_wh_R_prev_year,
          MIN(wh_D) AS min_wh_D_prev_year
        FROM jnmc_all_kwh
        WHERE YEAR(dt_time) = YEAR(DATE(?)) - 1
        GROUP BY device_id, host
      ) q5 ON q1.Device_Id = q5.device_id AND q1.Client_Id = q5.host
      ORDER BY q1.Client_Id;
      ",[$date, $date, $date, $date, $date,$date]);

    // Return the result as JSON response
    return response()->json($kwhData);
    }
}
