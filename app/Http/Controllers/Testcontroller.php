<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\energy; 
use Illuminate\Support\Facades\DB;


class testcontroller extends Controller
{
    public function generate_report(Request $request)
    {
        $host = $request->query('host');
        $device_id = $request->query('device_id');
        $date = $request->query('date');
    
        $results = DB::select("
        SELECT
        HOUR(q2.dt_time) AS HOUR,
        q2.dt_time,
        q2.HOST,
        q2.device_id,
        q2.wh_R - q1.wh_R AS value,
        q2.wh_D - q1.wh_D AS value1,
        q2.wh_1 - q1.wh_1 AS value2,
        q2.wh_2 - q1.wh_2 AS value3,
         q2.wh_3 - q1.wh_3 AS value4,
        q1.wh_R  AS wh_R,
        q1.wh_D  AS wh_D,
        q1.wh_1  AS wh_1,
        q1.wh_2  AS wh_2,
        q1.wh_3  AS wh_3
    FROM
        (
            SELECT dt_time, HOUR, HOST, device_id,
               FLOOR(wh_R/1000 ) AS wh_R,  
                FLOOR(wh_D / 1000) AS wh_D, 
               FLOOR(wh_1 / 1000) AS wh_1,
                FLOOR(wh_2 / 1000) AS wh_2,
             FLOOR(wh_3 / 1000) AS wh_3
            FROM jnmc_all_kwh
            WHERE DATE(dt_time) = ? 
            AND HOUR = 23
            AND HOST = ?
            AND device_id = ?
        ) q1
    
    LEFT JOIN
    
        (
            SELECT dt_time, HOUR, HOST, device_id, 
               FLOOR(wh_R/1000 ) AS wh_R, 
               FLOOR(wh_D / 1000) AS wh_D,
                FLOOR(wh_1 / 1000) AS wh_1,
                FLOOR(wh_2 / 1000) AS wh_2,
                FLOOR(wh_3 / 1000) AS wh_3
            FROM jnmc_all_kwh
            WHERE DATE(dt_time) = DATE(? + INTERVAL 1 day)
            AND HOUR = 0
            AND HOST = ?
            AND device_id = ?
        ) q2
    
    ON q1.device_id = q2.device_id
    
    UNION
    
    SELECT
        HOUR(next_dt_time) AS HOUR,
        next_dt_time AS dt_time,
        HOST,
        device_id,
         FLOOR(value / 1000) AS value,
        FLOOR(value1 / 1000) AS value1,
        FLOOR(value2 / 1000) AS value2,
        FLOOR(value3 / 1000) AS value3,
        FLOOR(value4 / 1000) AS value4,
        FLOOR(wh_R / 1000) AS wh_R,
        FLOOR(wh_D / 1000) AS wh_D,
        FLOOR(wh_1 / 1000) AS wh_1,
        FLOOR(wh_2 / 1000) AS wh_2,
        FLOOR(wh_3 / 1000) AS wh_3
    FROM
        (
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
                @prev_dt_time AS next_dt_time,
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
            FROM
                (
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
                    FROM
                        jnmc_all_kwh
                    WHERE
                        DATE(dt_time) = ?
                    AND HOST = ?
                    AND device_id = ?
                    ORDER BY
                        dt_time DESC
                ) AS reversed_subquery,
                (SELECT @prev_dt_time := NULL, @prev_wh_R := NULL, @prev_wh_D := NULL, @prev_wh_1 := NULL, @prev_wh_2 := NULL, @prev_wh_3 := NULL) AS vars
        ) AS calculated_data
    WHERE
        dt_time <> (
            SELECT
                MAX(dt_time)
            FROM
                jnmc_all_kwh
            WHERE
                DATE(dt_time) = ?
            AND HOST = ?
            AND device_id = ?
        )
    
    UNION
    
    -- This subquery fetches all rows for the selected date and device_id
    SELECT
        HOUR(dt_time) AS HOUR,
        dt_time,
        HOST,
        device_id,
        NULL AS VALUE,
        NULL AS value1,
        NULL AS value2,
        NULL AS value3,
        NULL AS value4,
        FLOOR(wh_R / 1000) AS wh_R,
        FLOOR(wh_D / 1000) AS wh_D,
        FLOOR(wh_1 / 1000) AS wh_1,
        FLOOR(wh_2 / 1000) AS wh_2,
        FLOOR(wh_3 / 1000) AS wh_3
    FROM
        jnmc_all_kwh
    WHERE
        DATE(dt_time) = ?
    AND HOST = ?
    AND device_id = ?
    
    ORDER BY
        dt_time", [
            $date, $host, $device_id,
            $date, $host, $device_id,
            $date, $host, $device_id,
            $date, $host, $device_id,
            $date, $host, $device_id
        ]);
    
        $skipped_results = [];
        foreach ($results as $index => $value) {
            if ($index % 2 === 0) { // Skip every alternate row (0-based index)
                continue;
            }
            $skipped_results[] = $value;
        }

        // Update the HOUR value if it's 0 (replace 0 with 24)
        foreach ($skipped_results as &$value) {
            if ($value->HOUR === 0) {
                $value->HOUR = 24;
            }
        }

        return $this->generate_html($date, $skipped_results, $request);

    }
        
    public function generate_html($date,$results,$request){
        $energy = new energy();
       
        
     $html_page['head'] = '
        <head>

        <title>JNMC-'.$date.'</title>
            <style  type="text/css" media="all">
            body {
                font-family: "Comic Sans MS", cursive, sans-serif;
            }
                table {
                    font-family: Comic Sans MS;
                    border-collapse: collapse;
                    width: 100%;
                }
                td{
                    /*height:20px !important;*/
                    line-height: 35px;

                }
                td, th {
                    border: 1px solid #dddddd;
                    text-align: left;
                    padding: 8px;
                }
                td, th {
                    position: relative;
                
                  }
                .std{ border: 0px; !important}
                thead{ background-color: #dddddd;}
                .flex-container {
                    display: flex;
                  }
                    /*tr:nth-child(even) {
                        background-color: #dddddd;
                    }*/
                          .header-report{

                            top: -60px;
                            left: -60px;
                            right: -60px;

                            /** Extra personal styles **/
                            background-color: #d1fec5;
                            color: white;
                            text-align: center;
                            line-height: 35px;
                          }
                          table { overflow: visible !important; }
                          thead { display: table-header-group !important;  }
                          tfoot { display: table-row-group !important;  }
                          tr { page-break-inside: avoid !important; }
                 @page { margin: 50px 25px 25px 25px;}
                 footer { position: fixed; bottom: -60px; left: 0px; right: 0px;}
                 .footer .page-number:after { content: counter(page); }
                 main { text-align: center;}
                 @page {
                    font-family: Comic Sans MS;
                    font-size:14px; !important;
/*page-break-before: always !important; */
                }
                .page_break {page-break-before: always !important; 
}
                .text{
                    color:black;
                   /* font-weight:bold;*/
                }
.category{
width:15%;
 border: 0px;

}

/*table{
page-break-inside: avoid !important;
break-inside: avoid-page !important;

}


tr ,td{
page-break-inside: avoid !important;
break-inside: avoid-page !important;
}*/
            </style>
        </head>
        <footer class="footer"  ><span class="page-number">[Page: </span> <span> ] </span>
         <span style="text-align:center padding:0 20%;">
         Heta Datain    www.hetadatain.com
         </span></footer>'
    ;
   
    $html_page['heading'] ='
        <div class="header-report">
            <table>
                <tr>
                    <td class="std" style="text-align: left;">
                        <span style="" >
                        <img   width="150px" src= "'.$energy->heta_logo.'" id=""></span>    
                    </td>
                    <td  class="std txt-align" style="text-align: right;">
                        <span style=" ">
                   <img   width="100px" src="'.$energy->plasto_logo.'" id=""> </span>
                    </td>
                </tr>
            </table>
        </div>
        <center>
            <div>
                <h2>'.$request->query('hostname').'/'.$request->query('devicename').' Report</h2>
                <h3 > Hourly Energy Report For Date - '.$date.' </h3>
            </div>
        </center><hr>';
        try{
    $html_page['heading_1'] ='<h3>Energy Consumption </h3>';
 
    $t_head1 ='<thead>
                    <tr>
                    <th>Hour</th>
                    <th>Device id</th>
                    <th>Kwh <br><br> (Recieved)</th>
                    <th>Kwh<br><br> (Delivered)</th>
                    <th>Kwh1</th>
                    <th>kwh2</th>
                    <th>kwh3</th>
                   
                    </tr>
                </thead>';


                $tbstring = '';
        foreach ($results as $value) {
            $tbstring .= '<tr>
                <td>' . $value->HOUR . '</td>
                <td>' . $value->device_id . '</td>
                <td>' . $value->value . '</td>
                <td>' . $value->value1 . '</td>
                <td>' . $value->value2 . '</td>
                <td>' . $value->value3 . '</td>
                <td>' . $value->value4 . '</td>
                </tr>';
        }
                
                // Add Starting and Ending Reading rows at the beginning and end of the table
                $tbstring = '<tr>
                    <td colspan="2">Starting Reading</td>
                    <td>' . $results[0]->wh_R . '</td>
                    <td>' . $results[0]->wh_D . '</td>
                    <td>' . $results[0]->wh_1 . '</td>
                    <td>' . $results[0]->wh_2 . '</td>
                    <td>' . $results[0]->wh_3 . '</td>
                    </tr>' . $tbstring;
                
                $tbstring .= '<tr>
                    <td colspan="2">Ending Reading</td>
                    <td>' . $results[count($results) - 1]->wh_R . '</td>
                    <td>' . $results[count($results) - 1]->wh_D . '</td>
                    <td>' . $results[count($results) - 1]->wh_1 . '</td>
                    <td>' . $results[count($results) - 1]->wh_2 . '</td>
                    <td>' . $results[count($results) - 1]->wh_3 . '</td>
                    </tr>';

                
$tbstring = '';

foreach ($results as $value) {
    $tbstring .= '<tr>
        <td>' . $value->HOUR . '</td>
        <td>' . $value->device_id . '</td>
        <td>' . $value->wh_R . '</td>
        <td>' . $value->wh_D . '</td>
        <td>' . $value->wh_1 . '</td>
        <td>' . $value->wh_2 . '</td>
        <td>' . $value->wh_3 . '</td>
        </tr>';
}

// Add Starting and Ending Reading rows at the beginning and end of the table
$tbstring = '<tr>
    <td colspan="2">Starting Reading</td>
    <td>' . $results[0]->wh_R . '</td>
    <td>' . $results[0]->wh_D . '</td>
    <td>' . $results[0]->wh_1 . '</td>
    <td>' . $results[0]->wh_2 . '</td>
    <td>' . $results[0]->wh_3 . '</td>
    </tr>' . $tbstring;

$tbstring .= '<tr>
    <td colspan="2">Ending Reading</td>
    <td>' . $results[count($results) - 1]->wh_R . '</td>
    <td>' . $results[count($results) - 1]->wh_D . '</td>
    <td>' . $results[count($results) - 1]->wh_1 . '</td>
    <td>' . $results[count($results) - 1]->wh_2 . '</td>
    <td>' . $results[count($results) - 1]->wh_3 . '</td>
    </tr>';

// Calculate and add the total row
$total_R = (int)$results[count($results) - 1]->wh_R - (int)$results[0]->wh_R;
$total_D = (int)$results[count($results) - 1]->wh_D - (int)$results[0]->wh_D;
$total_1 = (int)$results[count($results) - 1]->wh_1 - (int)$results[0]->wh_1;
$total_2 = (int)$results[count($results) - 1]->wh_2 - (int)$results[0]->wh_2;
$total_3 = (int)$results[count($results) - 1]->wh_3 - (int)$results[0]->wh_3;

$tbstring .= '<tr>
    <td colspan="2">Total</td>
    <td>' . $total_R . '</td>
    <td>' . $total_D . '</td>
    <td>' . $total_1 . '</td>
    <td>' . $total_2 . '</td>
    <td>' . $total_3 . '</td>
    </tr>';

$tbstring = '';

foreach ($results as $value) {
    $tbstring .= '<tr>
        <td>' . $value->HOUR . '</td>
        <td>' . $value->device_id . '</td>
        <td>' . $value->value . '</td>
        <td>' . $value->value1 . '</td>
        <td>' . $value->value2 . '</td>
        <td>' . $value->value3 . '</td>
        <td>' . $value->value4 . '</td>
        </tr>';
}

// Add Starting and Ending Reading rows at the beginning and end of the table
$tbstring = '<tr>
    <td colspan="2">Starting Reading</td>
    <td>' . $results[0]->wh_R . '</td>
    <td>' . $results[0]->wh_D . '</td>
    <td>' . $results[0]->wh_1 . '</td>
    <td>' . $results[0]->wh_2 . '</td>
    <td>' . $results[0]->wh_3 . '</td>
    </tr>' . $tbstring;

$tbstring .= '<tr>
    <td colspan="2">Ending Reading</td>
    <td>' . $results[count($results) - 1]->wh_R . '</td>
    <td>' . $results[count($results) - 1]->wh_D . '</td>
    <td>' . $results[count($results) - 1]->wh_1 . '</td>
    <td>' . $results[count($results) - 1]->wh_2 . '</td>
    <td>' . $results[count($results) - 1]->wh_3 . '</td>
    </tr>';

// Calculate and add the total row
$total_R = (int)$results[count($results) - 1]->wh_R - (int)$results[0]->wh_R;
$total_D = (int)$results[count($results) - 1]->wh_D - (int)$results[0]->wh_D;
$total_1 = (int)$results[count($results) - 1]->wh_1 - (int)$results[0]->wh_1;
$total_2 = (int)$results[count($results) - 1]->wh_2 - (int)$results[0]->wh_2;
$total_3 = (int)$results[count($results) - 1]->wh_3 - (int)$results[0]->wh_3;

$tbstring .= '<tr>
    <td colspan="2">Total</td>
    <td>' . $total_R . '</td>
    <td>' . $total_D . '</td>
    <td>' . $total_1 . '</td>
    <td>' . $total_2 . '</td>
    <td>' . $total_3 . '</td>
    </tr>';

$t_body1 = '<tbody>' . $tbstring . '</tbody>';

$table1 = '<table>' . $t_head1 . '' . $t_body1 . '</table>';

$html_page['body'] =
    '<body>' . $html_page['heading'] . ''
    . $html_page['heading_1'] . '' . $table1 . '</body>';

$html_page['page'] = '<html>' . $html_page['head'] . '' . $html_page['body'] . '</html>';

return $html_page['page'];

        }catch(\Exception $e){
            return  '<html>'.$html_page['head'].'<body>'.$html_page['heading'].'<h4>Report not Found</h4></body></html>'; 
        }
}
}

